<?php

namespace App\Domain\Shared\ValueObjects;

use InvalidArgumentException;

/**
 * A quantity of a product, measured in thousandths of one unit of measure.
 *
 * Quantities are not money. They are exact decimals that may be fractional
 * against the product's unit of measure, and they are stored as scaled integers
 * so that a stock balance, which is a sum of movements, is exact.
 */
final class Quantity
{
    public const SCALE = 3;

    private function __construct(private readonly ScaledDecimal $amount) {}

    public static function of(int $scaledValue): self
    {
        return new self(ScaledDecimal::of($scaledValue, self::SCALE));
    }

    public static function parse(string $amount): self
    {
        return new self(ScaledDecimal::parse($amount, self::SCALE));
    }

    /**
     * Parse a quantity that must not carry more decimal places than the unit
     * of measure allows.
     */
    public static function parseWithinPrecision(string $amount, int $precision): self
    {
        if ($precision < 0 || $precision > self::SCALE) {
            throw new InvalidArgumentException('The unit precision is out of range.');
        }

        $quantity = self::parse($amount);

        $fraction = explode('.', ltrim($amount, '-'))[1] ?? '';

        if (strlen(rtrim($fraction, '0')) > $precision) {
            throw new InvalidArgumentException("The quantity has more than {$precision} decimal places.");
        }

        return $quantity;
    }

    public function scaledValue(): int
    {
        return $this->amount->value;
    }

    public function toString(): string
    {
        return $this->amount->toString();
    }

    public function add(self $other): self
    {
        return new self($this->amount->add($other->amount));
    }

    public function subtract(self $other): self
    {
        return new self($this->amount->subtract($other->amount));
    }

    public function negate(): self
    {
        return new self($this->amount->negate());
    }

    public function isNegative(): bool
    {
        return $this->amount->isNegative();
    }

    public function isZero(): bool
    {
        return $this->amount->isZero();
    }

    public function equals(self $other): bool
    {
        return $this->amount->equals($other->amount);
    }

    public function compare(self $other): int
    {
        return $this->amount->compare($other->amount);
    }
}
