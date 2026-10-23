<?php

namespace App\Support\Mfa;

use App\Models\MfaChallenge;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Multi-factor enrolment, challenge, and verification rules.
 *
 * The second factor is enforced where a credential is issued. A successful
 * password check for an enrolled account does not produce a session or a
 * personal access token; it produces a short-lived, single-use challenge that
 * must be redeemed with a second factor before the credential exists.
 */
class MfaManager
{
    public const PURPOSE_SESSION = 'session';

    public const PURPOSE_TOKEN = 'token';

    /**
     * Failed verification attempts allowed before a challenge is burned.
     */
    public const MAX_ATTEMPTS = 5;

    public const CHALLENGE_LIFETIME_MINUTES = 5;

    public const RECOVERY_CODE_COUNT = 8;

    /**
     * Recovery codes avoid characters that are easy to confuse when they are
     * read from a screen and typed.
     */
    private const RECOVERY_CODE_ALPHABET = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

    public function isEnrolled(User $user): bool
    {
        return $user->mfa_enrolled_at !== null && $user->mfa_secret !== null;
    }

    /**
     * Create a pending enrolment and return the secret for the client to
     * register in an authenticator application.
     *
     * @return array{secret: string, otpauth_uri: string}
     */
    public function beginEnrollment(User $user): array
    {
        if ($this->isEnrolled($user)) {
            throw new MfaException(
                MfaException::CODE_ALREADY_ENROLLED,
                'Multi-factor authentication is already enrolled for this account.'
            );
        }

        $secret = Totp::generateSecret();

        $user->forceFill(['mfa_pending_secret' => $secret])->save();

        return [
            'secret' => $secret,
            'otpauth_uri' => $this->otpauthUri($user, $secret),
        ];
    }

    /**
     * Confirm a pending enrolment and return the recovery codes.
     *
     * @return array<int, string>
     */
    public function confirmEnrollment(User $user, string $code): array
    {
        $secret = $user->mfa_pending_secret;

        if ($secret === null) {
            throw new MfaException(
                MfaException::CODE_NO_PENDING_ENROLLMENT,
                'There is no pending multi-factor enrolment for this account.'
            );
        }

        if (Totp::verify($secret, $code) === null) {
            throw new MfaException(MfaException::CODE_INVALID, 'The verification code is not valid.');
        }

        $recoveryCodes = $this->generateRecoveryCodes();

        $user->forceFill([
            'mfa_secret' => $secret,
            'mfa_pending_secret' => null,
            'mfa_recovery_codes' => array_map(
                fn (string $recoveryCode): string => hash('sha256', $this->normalizeRecoveryCode($recoveryCode)),
                $recoveryCodes
            ),
            'mfa_enrolled_at' => now(),
            'mfa_last_used_counter' => null,
        ])->save();

        return $recoveryCodes;
    }

    /**
     * Remove multi-factor authentication from an account.
     */
    public function disable(User $user, string $code): void
    {
        if (! $this->isEnrolled($user)) {
            throw new MfaException(
                MfaException::CODE_NOT_ENROLLED,
                'Multi-factor authentication is not enrolled for this account.'
            );
        }

        if (! $this->verifySecondFactor($user, $code)) {
            throw new MfaException(MfaException::CODE_INVALID, 'The verification code is not valid.');
        }

        $user->forceFill([
            'mfa_secret' => null,
            'mfa_pending_secret' => null,
            'mfa_recovery_codes' => null,
            'mfa_enrolled_at' => null,
            'mfa_last_used_counter' => null,
        ])->save();
    }

    /**
     * Issue a single-use challenge for a password-authenticated account.
     *
     * @return array{challenge: string, expires_at: Carbon}
     */
    public function issueChallenge(User $user, string $purpose, ?string $clientName = null): array
    {
        $token = Str::random(64);

        $expiresAt = now()->addMinutes(self::CHALLENGE_LIFETIME_MINUTES);

        MfaChallenge::create([
            'user_id' => $user->getKey(),
            'challenge_hash' => hash('sha256', $token),
            'purpose' => $purpose,
            'client_name' => $clientName,
            'expires_at' => $expiresAt,
        ]);

        return [
            'challenge' => $token,
            'expires_at' => $expiresAt,
        ];
    }

    /**
     * Find the challenge a client presented.
     */
    public function findChallenge(string $token): ?MfaChallenge
    {
        if ($token === '') {
            return null;
        }

        return MfaChallenge::query()
            ->where('challenge_hash', hash('sha256', $token))
            ->first();
    }

    /**
     * Burn a challenge so that it can never be redeemed again.
     *
     * The update is conditional on the challenge not already being consumed,
     * so two concurrent redemptions cannot both succeed.
     */
    public function consumeChallenge(MfaChallenge $challenge): void
    {
        $consumed = MfaChallenge::query()
            ->whereKey($challenge->getKey())
            ->whereNull('consumed_at')
            ->update(['consumed_at' => now()]);

        if ($consumed === 0) {
            throw new MfaException(
                MfaException::CODE_CHALLENGE_INVALID,
                'The multi-factor challenge is no longer valid.'
            );
        }
    }

    /**
     * Record a failed verification attempt and lock the challenge out once the
     * attempt bound is reached.
     */
    public function recordFailedAttempt(MfaChallenge $challenge): void
    {
        $attempts = $challenge->attempts + 1;

        $challenge->forceFill(['attempts' => $attempts])->save();

        if ($attempts >= self::MAX_ATTEMPTS) {
            $challenge->forceFill(['consumed_at' => now()])->save();

            throw new MfaException(
                MfaException::CODE_LOCKED,
                'Too many verification attempts. Authenticate again to continue.'
            );
        }
    }

    /**
     * Verify a second factor for an account.
     *
     * A time-based code is accepted when it is valid and has not already been
     * used. A recovery code is accepted once and is then removed.
     */
    public function verifySecondFactor(User $user, string $code): bool
    {
        if ($this->verifyRecoveryCode($user, $code)) {
            return true;
        }

        $counter = Totp::verify(
            (string) $user->mfa_secret,
            $code,
            Totp::DEFAULT_WINDOW,
            $user->mfa_last_used_counter
        );

        if ($counter === null) {
            return false;
        }

        $user->forceFill(['mfa_last_used_counter' => $counter])->save();

        return true;
    }

    /**
     * Accept a recovery code and remove it from the account.
     */
    public function verifyRecoveryCode(User $user, string $code): bool
    {
        $normalized = $this->normalizeRecoveryCode($code);

        if ($normalized === '') {
            return false;
        }

        $hash = hash('sha256', $normalized);
        $stored = $user->mfa_recovery_codes ?? [];

        foreach ($stored as $index => $candidate) {
            if (! hash_equals((string) $candidate, $hash)) {
                continue;
            }

            unset($stored[$index]);

            $user->forceFill(['mfa_recovery_codes' => array_values($stored)])->save();

            return true;
        }

        return false;
    }

    /**
     * The number of recovery codes the account still holds.
     */
    public function remainingRecoveryCodes(User $user): int
    {
        return count($user->mfa_recovery_codes ?? []);
    }

    /**
     * @return array<int, string>
     */
    private function generateRecoveryCodes(int $count = self::RECOVERY_CODE_COUNT): array
    {
        $codes = [];

        for ($index = 0; $index < $count; $index++) {
            $characters = '';

            for ($position = 0; $position < 12; $position++) {
                $characters .= self::RECOVERY_CODE_ALPHABET[random_int(0, strlen(self::RECOVERY_CODE_ALPHABET) - 1)];
            }

            $codes[] = implode('-', str_split($characters, 4));
        }

        return $codes;
    }

    private function normalizeRecoveryCode(string $code): string
    {
        return strtoupper((string) preg_replace('/[^A-Za-z0-9]/', '', $code));
    }

    private function otpauthUri(User $user, string $secret): string
    {
        $label = rawurlencode('flowOS:'.($user->email ?? (string) $user->getKey()));

        return 'otpauth://totp/'.$label
            .'?secret='.$secret
            .'&issuer='.rawurlencode('flowOS')
            .'&algorithm=SHA1'
            .'&digits='.Totp::DIGITS
            .'&period='.Totp::PERIOD;
    }
}
