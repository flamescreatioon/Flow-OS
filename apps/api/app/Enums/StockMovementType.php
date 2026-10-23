<?php

namespace App\Enums;

/**
 * The reason a stock movement was recorded.
 *
 * The direction is a property of the category, not of the caller. A receipt
 * increases stock and a sale decreases it, so a client states a magnitude and
 * the server applies the direction. Only the categories that express a
 * variance carry a signed quantity.
 */
enum StockMovementType: string
{
    case Opening = 'opening';

    case Receipt = 'receipt';

    case Sale = 'sale';

    case Return = 'return';

    case Adjustment = 'adjustment';

    case TransferIn = 'transfer_in';

    case TransferOut = 'transfer_out';

    case Count = 'count';

    /**
     * How the recorded quantity relates to the magnitude the caller stated.
     *
     * 1 increases stock, -1 decreases it, and 0 means the caller states the
     * signed quantity directly.
     */
    public function direction(): int
    {
        return match ($this) {
            self::Opening, self::Receipt, self::Return, self::TransferIn => 1,
            self::Sale, self::TransferOut => -1,
            self::Adjustment, self::Count => 0,
        };
    }

    /**
     * Whether the caller states a signed quantity rather than a magnitude.
     */
    public function acceptsSignedQuantity(): bool
    {
        return $this->direction() === 0;
    }

    /**
     * Whether a movement of this category may record a zero quantity.
     *
     * A count that finds no variance is still evidence that a count
     * happened, so it is recorded.
     */
    public function allowsZeroQuantity(): bool
    {
        return $this === self::Count;
    }

    /**
     * Whether the movement is one half of a transfer pair.
     */
    public function isTransfer(): bool
    {
        return $this === self::TransferIn || $this === self::TransferOut;
    }
}
