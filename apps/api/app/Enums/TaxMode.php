<?php

namespace App\Enums;

/**
 * How the organization's tax rate applies to an invoice (ADR 0036).
 *
 * Exclusive adds the tax on top of the line totals. Inclusive treats the
 * stated price as containing the tax, so the tax is extracted from it. The
 * mode is snapshotted on the invoice at issue time.
 */
enum TaxMode: string
{
    case Exclusive = 'exclusive';

    case Inclusive = 'inclusive';
}
