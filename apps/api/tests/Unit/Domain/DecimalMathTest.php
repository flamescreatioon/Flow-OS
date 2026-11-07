<?php

namespace Tests\Unit\Domain;

use App\Domain\Shared\Services\DecimalMath;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class DecimalMathTest extends TestCase
{
    public function test_a_line_total_rounds_half_up_at_the_currency_scale(): void
    {
        // 2.500 (scale 3) x 1.3333 (scale 4) = 3.33325 -> 3.3333.
        $this->assertSame(33333, DecimalMath::multiplyRoundHalfUp(2500, 13333, 3, 4, 4));

        // 2.500 x 1.3332 = 3.3330, exact.
        $this->assertSame(33330, DecimalMath::multiplyRoundHalfUp(2500, 13332, 3, 4, 4));

        // 12.500 x 2500.5000 = 31256.2500, exact.
        $this->assertSame(312562500, DecimalMath::multiplyRoundHalfUp(12500, 25005000, 3, 4, 4));
    }

    public function test_an_exact_half_rounds_up(): void
    {
        // 1.001 (scale 3) x 0.1500 (scale 4) = 0.15015 -> 0.1502 at scale 4.
        $this->assertSame(1502, DecimalMath::multiplyRoundHalfUp(1001, 1500, 3, 4, 4));

        // 1.001 x 0.1400 = 0.14014 -> 0.1401, below the half.
        $this->assertSame(1401, DecimalMath::multiplyRoundHalfUp(1001, 1400, 3, 4, 4));

        // 1.000 x 0.0004 = 0.0004, exact at scale 4.
        $this->assertSame(4, DecimalMath::multiplyRoundHalfUp(1000, 4, 3, 4, 4));
    }

    public function test_zero_values_multiply_to_zero(): void
    {
        $this->assertSame(0, DecimalMath::multiplyRoundHalfUp(0, 25005000, 3, 4, 4));
        $this->assertSame(0, DecimalMath::multiplyRoundHalfUp(12500, 0, 3, 4, 4));
    }

    public function test_negative_products_round_on_their_magnitude(): void
    {
        // -0.0005 rounds to -0.0001 (mirror of +0.0005).
        $this->assertSame(-1, DecimalMath::divideRoundHalfUp(-5, 10));
        $this->assertSame(1, DecimalMath::divideRoundHalfUp(5, 10));
    }

    public function test_a_product_that_overflows_the_integer_range_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        DecimalMath::multiplyRoundHalfUp(PHP_INT_MAX, 2, 0, 0, 0);
    }

    public function test_division_by_zero_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        DecimalMath::divideRoundHalfUp(10, 0);
    }

    public function test_division_without_remainder_is_exact(): void
    {
        $this->assertSame(12345, DecimalMath::divideRoundHalfUp(12345, 1));
        $this->assertSame(4, DecimalMath::divideRoundHalfUp(8, 2));
    }

    public function test_division_rounds_half_up(): void
    {
        $this->assertSame(2, DecimalMath::divideRoundHalfUp(15, 10));
        $this->assertSame(1, DecimalMath::divideRoundHalfUp(14, 10));
        $this->assertSame(-2, DecimalMath::divideRoundHalfUp(-15, 10));
    }
}
