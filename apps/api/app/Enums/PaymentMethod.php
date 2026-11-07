<?php

namespace App\Enums;

/**
 * How an expense was paid, in operational terms (ADR 0039).
 *
 * This is a record of the payment channel, not a settlement engine: cash
 * accounts, till sessions, and reconciliation are future Finance work.
 */
enum PaymentMethod: string
{
    case Cash = 'cash';

    case BankTransfer = 'bank_transfer';

    case Card = 'card';

    case Other = 'other';
}
