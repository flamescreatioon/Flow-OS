<?php

namespace App\Enums;

/**
 * The lifecycle state of an expense (ADR 0039).
 *
 * A recorded expense is editable. Void is terminal: the record is retained
 * as evidence and excluded from aggregates; there is no delete path.
 */
enum ExpenseStatus: string
{
    case Recorded = 'recorded';

    case Voided = 'voided';
}
