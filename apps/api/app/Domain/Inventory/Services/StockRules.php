<?php

namespace App\Domain\Inventory\Services;

use App\Domain\Shared\ValueObjects\Quantity;
use App\Enums\StockMovementType;
use InvalidArgumentException;
use LogicException;

/**
 * The rules that decide what a stock movement means.
 *
 * These rules are pure. They take a movement category and a quantity and
 * return the signed quantity that the movement applies, or reject the
 * request. They do not read or write any record.
 */
final class StockRules
{
    /**
     * The signed quantity a movement applies.
     *
     * A category with a fixed direction takes a magnitude and the server
     * applies the direction, so a client cannot record a receipt that reduces
     * stock. A category that expresses a variance takes a signed quantity.
     */
    public static function resolveQuantity(StockMovementType $type, Quantity $stated): Quantity
    {
        if ($type->acceptsSignedQuantity()) {
            if ($stated->isZero() && ! $type->allowsZeroQuantity()) {
                throw new InvalidArgumentException('A movement quantity must not be zero.');
            }

            return $stated;
        }

        if ($stated->isNegative()) {
            throw new InvalidArgumentException(
                "A {$type->value} movement states a magnitude, not a signed quantity."
            );
        }

        if ($stated->isZero()) {
            throw new InvalidArgumentException('A movement quantity must not be zero.');
        }

        return $type->direction() > 0 ? $stated : $stated->negate();
    }

    /**
     * The variance between a counted quantity and the current balance.
     */
    public static function countVariance(Quantity $counted, Quantity $current): Quantity
    {
        if ($counted->isNegative()) {
            throw new InvalidArgumentException('A counted quantity must not be negative.');
        }

        return $counted->subtract($current);
    }

    /**
     * A correction must move stock in the opposite direction to the movement
     * it corrects.
     *
     * A full reversal states the exact negation of the original. A partial
     * correction states the difference that was wrong, which may be smaller.
     * Either way the correction must not move stock in the same direction as
     * the movement it corrects.
     */
    public static function assertCorrectionOpposes(Quantity $original, Quantity $correction): void
    {
        if ($original->isZero() || $correction->isZero()) {
            throw new LogicException('A correction must not be a zero quantity.');
        }

        if ($original->isNegative() === $correction->isNegative()) {
            throw new LogicException('A correction must move stock in the opposite direction to the movement it corrects.');
        }
    }
}
