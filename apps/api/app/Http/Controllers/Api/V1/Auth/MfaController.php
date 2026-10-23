<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Enums\UserStatus;
use App\Http\Requests\Auth\ConfirmMfaRequest;
use App\Http\Requests\Auth\DisableMfaRequest;
use App\Http\Requests\Auth\VerifyMfaRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Support\Mfa\MfaException;
use App\Support\Mfa\MfaManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MfaController
{
    /**
     * Start an enrolment and return the secret for the authenticator app.
     */
    public function enroll(Request $request, MfaManager $mfa): JsonResponse
    {
        try {
            $enrollment = $mfa->beginEnrollment($request->user());
        } catch (MfaException $exception) {
            return $this->failure($exception);
        }

        return response()->json(['data' => $enrollment], 201);
    }

    /**
     * Confirm an enrolment with a code from the authenticator app and return
     * the recovery codes. The recovery codes are shown exactly once.
     */
    public function confirm(ConfirmMfaRequest $request, MfaManager $mfa): JsonResponse
    {
        try {
            $recoveryCodes = $mfa->confirmEnrollment($request->user(), $request->validated('code'));
        } catch (MfaException $exception) {
            return $this->failure($exception);
        }

        return response()->json(['data' => ['recovery_codes' => $recoveryCodes]]);
    }

    /**
     * Redeem a multi-factor challenge for the credential it was issued for.
     *
     * This endpoint is unauthenticated on purpose: the challenge is the
     * credential until it is redeemed.
     */
    public function verify(VerifyMfaRequest $request, MfaManager $mfa): JsonResponse
    {
        $credentials = $request->validated();

        $challenge = $mfa->findChallenge($credentials['challenge']);

        if (! $challenge || ! $challenge->isRedeemable()) {
            return $this->failure(new MfaException(
                MfaException::CODE_CHALLENGE_INVALID,
                'The multi-factor challenge is no longer valid.'
            ));
        }

        $user = $challenge->user;

        if (! $user) {
            return $this->failure(new MfaException(
                MfaException::CODE_CHALLENGE_INVALID,
                'The multi-factor challenge is no longer valid.'
            ));
        }

        if ($user->status !== UserStatus::Active) {
            return response()->json([
                'error' => ['code' => 'ACCOUNT_INACTIVE', 'message' => 'This account is not active.'],
            ], 403);
        }

        try {
            if (! $mfa->verifySecondFactor($user, $credentials['code'])) {
                $mfa->recordFailedAttempt($challenge);

                return $this->failure(new MfaException(
                    MfaException::CODE_INVALID,
                    'The verification code is not valid.'
                ));
            }

            $mfa->consumeChallenge($challenge);
        } catch (MfaException $exception) {
            return $this->failure($exception);
        }

        return $challenge->purpose === MfaManager::PURPOSE_TOKEN
            ? $this->issueToken($user, $challenge->client_name)
            : $this->issueSession($request, $user);
    }

    /**
     * Remove multi-factor authentication from the authenticated account.
     */
    public function disable(DisableMfaRequest $request, MfaManager $mfa): JsonResponse
    {
        try {
            $mfa->disable($request->user(), $request->validated('code'));
        } catch (MfaException $exception) {
            return $this->failure($exception);
        }

        return response()->json([], 204);
    }

    private function issueSession(Request $request, User $user): JsonResponse
    {
        Auth::guard('web')->login($user);
        $request->session()->regenerate();
        $user->forceFill(['last_login_at' => now()])->save();

        return (new UserResource($user))->response();
    }

    private function issueToken(User $user, ?string $clientName): JsonResponse
    {
        $token = $user->createToken($clientName ?: 'Mobile', ['*']);
        $user->forceFill(['last_login_at' => now()])->save();

        return response()->json([
            'token' => $token->plainTextToken,
            'token_type' => 'Bearer',
            'user' => new UserResource($user),
        ], 201);
    }

    private function failure(MfaException $exception): JsonResponse
    {
        return response()->json([
            'error' => [
                'code' => $exception->reason,
                'message' => $exception->getMessage(),
            ],
        ], match ($exception->reason) {
            MfaException::CODE_LOCKED => 429,
            MfaException::CODE_NOT_ENROLLED,
            MfaException::CODE_NO_PENDING_ENROLLMENT,
            MfaException::CODE_ALREADY_ENROLLED => 409,
            default => 401,
        });
    }
}
