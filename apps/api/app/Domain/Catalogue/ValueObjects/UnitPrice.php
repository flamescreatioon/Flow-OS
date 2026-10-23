<?php

namespace App\Domain\Catalogue\ValueObjects;

use App\Domain\Shared\ValueObjects\ScaledDecimal;

/**
 * A unit price in the organization's currency.
 *
 * A unit price may carry more precision than a final transaction total, so it
 * keeps four decimal places. Intermediate results are never truncated.
 */
final class UnitPrice
{
    public const SCALE = 4;

    private function __construct(private readonly ScaledDecimal $amount) {}

    public static function of(int $scaledValue): self
    {
        return new self(ScaledDecimal::of($scaledValue, self::SCALE));
    }

    public static function parse(string $amount): self
    {
        return new self(ScaledDecimal::parse($amount, self::SCALE));
    }

    public function scaledValue(): int
    {
        return $this->amount->value;
    }

    public function toString(): string
    {
        return $this->amount->toString();
    }

    public function isZero(): bool
    {
        return $this->amount->isZero();
    }

    public function isNegative(): bool
    {
        return $this->amount->isNegative();
    }

    public function equals(self $other): bool
    {
        return $this->amount->equals($other->amount);
    }
}
