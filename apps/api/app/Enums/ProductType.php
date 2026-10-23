<?php

namespace App\Enums;

/**
 * How a catalogue product behaves in inventory.
 */
enum ProductType: string
{
    /**
     * Tracked in inventory. Only a stocked product may have stock movements
     * and a stock balance.
     */
    case Stocked = 'stocked';

    /**
     * Bought and resold without being tracked in inventory.
     */
    case NonStocked = 'non_stocked';

    /**
     * Work performed rather than a physical item. It has no stock at all.
     */
    case Service = 'service';

    public function tracksStock(): bool
    {
        return $this === self::Stocked;
    }
}
