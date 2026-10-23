<?php

namespace App\Domain\Shared\ValueObjects;

use InvalidArgumentException;

/**
 * An exact decimal represented as a scaled integer.
 *
 * The canonical wire format is a locale-neutral decimal string with a period
 * separator and no commas, for example "3.125". The parsed value is stored as
 * an integer number of 10^-scale units, so arithmetic is exact and never
 * touches binary floating point.
 *
 * The accepted and rejected input forms match the shared TypeScript money
 * parser so that every client applies the same canonicalisation rules.
 */
final class ScaledDecimal
{
    private function __construct(
        public readonly int $value,
        public readonly int $scale,
    ) {}

    /**
     * Build a decimal from an already scaled integer.
     */
    public static function of(int $value, int $scale): self
    {
        if ($scale < 0 || $scale > 12) {
            throw new InvalidArgumentException('The scale must be between 0 and 12.');
        }

        return new self($value, $scale);
    }

    /**
     * Parse a canonical decimal string.
     *
     * Rejects an empty string, a lone sign, a leading plus sign, more than one
     * decimal point, a missing integer part such as ".50", a trailing decimal
     * point such as "12.", non-digit characters, thousands separators, and
     * more fractional digits than the scale allows.
     */
    public static function parse(string $amount, int $scale): self
    {
        if ($scale < 0 || $scale > 12) {
            throw new InvalidArgumentException('The scale must be between 0 and 12.');
        }

        if ($amount === '') {
            throw new InvalidArgumentException('The amount is empty.');
        }

        if ($amount[0] === '+') {
            throw new InvalidArgumentException('A leading plus sign is not allowed.');
        }

        $negative = $amount[0] === '-';
        $digits = $negative ? substr($amount, 1) : $amount;

        if ($digits === '') {
            throw new InvalidArgumentException('The amount is only a sign.');
        }

        if (substr_count($digits, '.') > 1) {
            throw new InvalidArgumentException('More than one decimal point is not allowed.');
        }

        $parts = explode('.', $digits);

        if ($parts[0] === '') {
            throw new InvalidArgumentException('The integer part is required.');
        }

        if (! preg_match('/^\d+$/', $parts[0])) {
            throw new InvalidArgumentException('The integer part must contain only digits.');
        }

        $fraction = '';

        if (count($parts) === 2) {
            if ($parts[1] === '') {
                throw new InvalidArgumentException('A trailing decimal point is not allowed.');
            }

            if (! preg_match('/^\d+$/', $parts[1])) {
                throw new InvalidArgumentException('The fractional part must contain only digits.');
            }

            if (strlen($parts[1]) > $scale) {
                throw new InvalidArgumentException("The amount has more than {$scale} decimal places.");
            }

            $fraction = $parts[1];
        }

        $scaled = (int) $parts[0] * (10 ** $scale)
            + (int) str_pad($fraction, $scale, '0', STR_PAD_RIGHT);

        if ($scaled > PHP_INT_MAX - 1 || $scaled < PHP_INT_MIN + 1) {
            throw new InvalidArgumentException('The amount is out of range.');
        }

        return new self($negative ? -$scaled : $scaled, $scale);
    }

    /**
     * The canonical decimal string for this value.
     */
    public function toString(): string
    {
        $negative = $this->value < 0;
        $digits = (string) abs($this->value);

        if ($this->scale === 0) {
            return ($negative ? '-' : '').$digits;
        }

        $digits = str_pad($digits, $this->scale + 1, '0', STR_PAD_LEFT);

        return ($negative ? '-' : '')
            .substr($digits, 0, -$this->scale)
            .'.'
            .substr($digits, -$this->scale);
    }

    public function add(self $other): self
    {
        $this->assertSameScale($other);

        return new self($this->value + $other->value, $this->scale);
    }

    public function subtract(self $other): self
    {
        $this->assertSameScale($other);

        return new self($this->value - $other->value, $this->scale);
    }

    /**
     * Multiply two decimals exactly. The result carries the sum of the scales,
     * so no intermediate precision is lost.
     */
    public function multiply(self $other): self
    {
        return new self($this->value * $other->value, $this->scale + $other->scale);
    }

    public function negate(): self
    {
        return new self(-$this->value, $this->scale);
    }

    public function isNegative(): bool
    {
        return $this->value < 0;
    }

    public function isZero(): bool
    {
        return $this->value === 0;
    }

    public function equals(self $other): bool
    {
        return $this->scale === $other->scale && $this->value === $other->value;
    }

    public function compare(self $other): int
    {
        $this->assertSameScale($other);

        return $this->value <=> $other->value;
    }

    public function isSameScale(self $other): bool
    {
        return $this->scale === $other->scale;
    }

    private function assertSameScale(self $other): void
    {
        if ($this->scale !== $other->scale) {
            throw new InvalidArgumentException('The scales do not match.');
        }
    }
}
