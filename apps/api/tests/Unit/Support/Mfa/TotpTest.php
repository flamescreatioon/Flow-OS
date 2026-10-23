<?php

namespace Tests\Unit\Support\Mfa;

use App\Support\Mfa\Totp;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class TotpTest extends TestCase
{
    /**
     * The RFC 6238 test secret "12345678901234567890" in base32.
     */
    private const RFC_SECRET = 'GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ';

    /**
     * RFC 6238 Appendix B, table with the truncated eight-digit values. The
     * six-digit codes under test are the last six digits of those values.
     */
    public static function rfc6238Vectors(): array
    {
        return [
            'time 59' => [59, '287082'],
            'time 1111111109' => [1111111109, '081804'],
            'time 1111111111' => [1111111111, '050471'],
            'time 1234567890' => [1234567890, '005924'],
            'time 2000000000' => [2000000000, '279037'],
            'time 20000000000' => [20000000000, '353130'],
        ];
    }

    #[DataProvider('rfc6238Vectors')]
    public function test_it_matches_the_rfc_6238_test_vectors(int $timestamp, string $expected): void
    {
        $this->assertSame($expected, Totp::codeAt(self::RFC_SECRET, Totp::counterAt($timestamp)));
    }

    public function test_it_verifies_a_code_within_the_allowed_window(): void
    {
        $timestamp = 1234567890;
        $code = Totp::codeAt(self::RFC_SECRET, Totp::counterAt($timestamp));

        $this->assertSame(Totp::counterAt($timestamp), Totp::verify(self::RFC_SECRET, $code, 1, null, $timestamp));
    }

    public function test_it_rejects_a_code_outside_the_allowed_window(): void
    {
        $timestamp = 1234567890;
        $code = Totp::codeAt(self::RFC_SECRET, Totp::counterAt($timestamp));

        $this->assertNull(Totp::verify(self::RFC_SECRET, $code, 1, null, $timestamp + (5 * Totp::PERIOD)));
    }

    public function test_it_rejects_a_code_at_or_below_the_last_used_counter(): void
    {
        $timestamp = 1234567890;
        $code = Totp::codeAt(self::RFC_SECRET, Totp::counterAt($timestamp));

        $this->assertNull(Totp::verify(self::RFC_SECRET, $code, 1, Totp::counterAt($timestamp), $timestamp));
    }

    public function test_it_rejects_malformed_codes(): void
    {
        $this->assertNull(Totp::verify(self::RFC_SECRET, ''));
        $this->assertNull(Totp::verify(self::RFC_SECRET, '12345'));
        $this->assertNull(Totp::verify(self::RFC_SECRET, '1234567'));
        $this->assertNull(Totp::verify(self::RFC_SECRET, 'abcdef'));
    }

    public function test_it_accepts_a_code_that_contains_separators(): void
    {
        $timestamp = 1234567890;
        $code = Totp::codeAt(self::RFC_SECRET, Totp::counterAt($timestamp));

        $this->assertSame(
            Totp::counterAt($timestamp),
            Totp::verify(self::RFC_SECRET, substr($code, 0, 3).' '.substr($code, 3), 1, null, $timestamp)
        );
    }

    public function test_base32_round_trips(): void
    {
        $this->assertSame('MZXW6', Totp::base32Encode('foo'));
        $this->assertSame('foo', Totp::base32Decode('MZXW6'));
        $this->assertSame('12345678901234567890', Totp::base32Decode(self::RFC_SECRET));
    }

    public function test_it_rejects_a_secret_that_is_not_base32(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Totp::base32Decode('0189');
    }

    public function test_generated_secrets_are_base32_and_unique(): void
    {
        $first = Totp::generateSecret();
        $second = Totp::generateSecret();

        $this->assertNotSame($first, $second);
        $this->assertSame(32, strlen($first));
        $this->assertSame(20, strlen(Totp::base32Decode($first)));
    }
}
