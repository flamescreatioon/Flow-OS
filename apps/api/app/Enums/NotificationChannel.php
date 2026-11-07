<?php

namespace App\Enums;

/**
 * A transport over which a notification is delivered (ADR 0037).
 *
 * The channel names the logical transport; the driver configured for it
 * (mail, log, or a future provider adapter) does the actual work.
 */
enum NotificationChannel: string
{
    case Email = 'email';

    case Sms = 'sms';

    case Whatsapp = 'whatsapp';
}
