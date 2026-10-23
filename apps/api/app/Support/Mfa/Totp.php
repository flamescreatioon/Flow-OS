<?php

namespace App\Support\Mfa;

use InvalidArgumentException;

/**
 * Time-based one-time password (RFC 6238) over the RFC 4226 dynamic truncation.
 *
 * The implementation uses only the PHP standard library so that the platform
 * does not take on a third-party dependency for a fully specified algorithm.
 * Correctness is demonstrated by the RFC 6238 test vectors in the unit tests.
 */
final class Totp
{
    public const ALGORITHM = 'sha1';

    public const DIGITS = 6;

    public const PERIOD = 30;

    /**
     * Number of periods before and after the current one that are accepted.
     */
    public const DEFAULT_WINDOW = 1;

    private const BASE32_ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    /**
     * Generate a new shared secret.
     */
    public static function generateSecret(int $bytes = 20): string
    {
        return self::base32Encode(random_bytes($bytes));
    }

    /**
     * The time-step counter for a point in time.
     */
    public static function counterAt(?int $timestamp = null): int
    {
        return intdiv($timestamp ?? time(), self::PERIOD);
    }

    /**
     * The one-time password for a specific time-step counter.
     */
    public static function codeAt(string $secret, int $counter): string
    {
        $hash = hash_hmac(self::ALGORITHM, pack('J', $counter), self::base32Decode($secret), true);

        $offset = ord($hash[19]) & 0x0F;

        $value = ((ord($hash[$offset]) & 0x7F) << 24)
            | ((ord($hash[$offset + 1]) & 0xFF) << 16)
            | ((ord($hash[$offset + 2]) & 0xFF) << 8)
            | (ord($hash[$offset + 3]) & 0xFF);

        return str_pad((string) ($value % (10 ** self::DIGITS)), self::DIGITS, '0', STR_PAD_LEFT);
    }

    /**
     * Verify a one-time password and return the accepted time-step counter.
     *
     * Returns null when the code is not valid. A counter at or below
     * $minimumCounter is refused so that an already used code cannot be
     * replayed inside its own validity window.
     */
    public static function verify(
        string $secret,
        string $code,
        int $window = self::DEFAULT_WINDOW,
        ?int $minimumCounter = null,
        ?int $timestamp = null
    ): ?int {
        $code = preg_replace('/\s+/', '', $code) ?? '';

        if (! preg_match('/^\d{'.self::DIGITS.'}$/', $code)) {
            return null;
        }

        $counter = self::counterAt($timestamp);

        for ($offset = -$window; $offset <= $window; $offset++) {
            $candidate = $counter + $offset;

            if ($minimumCounter !== null && $candidate <= $minimumCounter) {
                continue;
            }

            if (hash_equals(self::codeAt($secret, $candidate), $code)) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * Encode bytes using the RFC 4648 base32 alphabet without padding.
     */
    public static function base32Encode(string $bytes): string
    {
        $bits = '';

        foreach (str_split($bytes) as $byte) {
            $bits .= str_pad(decbin(ord($byte)), 8, '0', STR_PAD_LEFT);
        }

        $encoded = '';

        foreach (str_split($bits, 5) as $chunk) {
            $encoded .= self::BASE32_ALPHABET[bindec(str_pad($chunk, 5, '0', STR_PAD_RIGHT))];
        }

        return $encoded;
    }

    /**
     * Decode a base32 string into its bytes.
     */
    public static function base32Decode(string $base32): string
    {
        $normalized = strtoupper((string) preg_replace('/[^A-Z2-7]/i', '', $base32));

        if ($normalized === '') {
            throw new InvalidArgumentException('The secret is not valid base32.');
        }

        $bits = '';

        foreach (str_split($normalized) as $character) {
            $index = strpos(self::BASE32_ALPHABET, $character);

            if ($index === false) {
                throw new InvalidArgumentException('The secret is not valid base32.');
            }

            $bits .= str_pad(decbin($index), 5, '0', STR_PAD_LEFT);
        }

        $bytes = '';

        foreach (str_split($bits, 8) as $chunk) {
            if (strlen($chunk) === 8) {
                $bytes .= chr(bindec($chunk));
            }
        }

        return $bytes;
    }
}
