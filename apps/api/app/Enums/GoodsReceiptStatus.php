<?php

namespace App\Enums;

/**
 * The lifecycle state of a goods receipt.
 *
 * A receipt is finalized on creation (ADR 0035), so every receipt is recorded.
 * The enum exists so a later purchase-order state machine adds states without
 * changing the meaning of existing rows.
 */
enum GoodsReceiptStatus: string
{
    case Recorded = 'recorded';
}
