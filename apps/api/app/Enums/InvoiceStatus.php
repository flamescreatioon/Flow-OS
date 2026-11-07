<?php

namespace App\Enums;

/**
 * The lifecycle state of an invoice (ADR 0036).
 *
 * A draft is freely editable and carries no number. Issue snapshots the
 * commercial values and freezes the document. Void is terminal and retained
 * as evidence; corrections beyond voiding wait for the credit-note design.
 */
enum InvoiceStatus: string
{
    case Draft = 'draft';

    case Issued = 'issued';

    case Voided = 'voided';
}
