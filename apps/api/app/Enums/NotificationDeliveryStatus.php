<?php

namespace App\Enums;

/**
 * The state of one delivery attempt (ADR 0037).
 *
 * Queued means the request was accepted and the transport call is pending.
 * The queued job resolves the row to sent or failed; delivery state never
 * feeds back into business correctness.
 */
enum NotificationDeliveryStatus: string
{
    case Queued = 'queued';

    case Sent = 'sent';

    case Failed = 'failed';
}
