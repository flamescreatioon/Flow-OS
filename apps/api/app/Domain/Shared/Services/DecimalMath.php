<?php

namespace App\Domain\Shared\Services;

use InvalidArgumentException;

/**
 * Exact scaled-integer arithmetic shared by every commercial domain.
 *
 * Values arrive as scaled integers (the representation ADR 0033 uses for
 * money and quantities). Multiplication happens on the scaled integers and
 * the result is rounded half-up to a target scale, once, at the declared
 * boundary. No binary floating point is involved at any step.
 */
final class DecimalMath
{
    /**
     * Multiply two scaled integers and round half-up to the target scale.
     *
     * The intermediate product carries the sum of the input scales; the
     * result carries $targetScale. For example, a quantity at scale 3 times
     * a unit price at scale 4 rounded to scale 4 computes the line total of
     * a commercial line (ADR 0035).
     */
    public static function multiplyRoundHalfUp(
        int $left,
        int $right,
        int $leftScale,
        int $rightScale,
        int $targetScale
    ): int {
        if ($leftScale < 0 || $rightScale < 0 || $targetScale < 0) {
            throw new InvalidArgumentException('The scales must not be negative.');
        }

        $reduction = $leftScale + $rightScale - $targetScale;

        if ($reduction < 0) {
            throw new InvalidArgumentException('The target scale must not exceed the combined input scales.');
        }

        if ($reduction > 18) {
            throw new InvalidArgumentException('The scale reduction is too large for exact integer arithmetic.');
        }

        if ($left !== 0 && $right !== 0 && abs($left) > PHP_INT_MAX / abs($right)) {
            throw new InvalidArgumentException('The product of the amounts is out of range.');
        }

        return self::divideRoundHalfUp($left * $right, 10 ** $reduction);
    }

    /**
     * Divide two integers, rounding half-up away from zero on exact halves.
     *
     * A negative quotient rounds on its magnitude, so -1.5 rounds to -2, the
     * mirror of +1.5 rounding to +2.
     */
    public static function divideRoundHalfUp(int $value, int $divisor): int
    {
        if ($divisor === 0) {
            throw new InvalidArgumentException('The divisor must not be zero.');
        }

        $quotient = intdiv($value, $divisor);
        $remainder = $value % $divisor;

        if ($remainder === 0) {
            return $quotient;
        }

        $doubledRemainder = abs($remainder) * 2;

        if ($doubledRemainder >= abs($divisor)) {
            return $quotient + ($value > 0 ? 1 : -1);
        }

        return $quotient;
    }
}
