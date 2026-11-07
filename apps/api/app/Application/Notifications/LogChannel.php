<?php

namespace App\Application\Notifications;

use Illuminate\Support\Facades\Log;

/**
 * Log-only delivery, the default driver for SMS and WhatsApp until a real
 * provider is selected (ADR 0037).
 *
 * The structured event carries safe contextual identifiers only — never the
 * recipient address, never the message body.
 */
final class LogChannel implements DeliveryChannel
{
    public function send(string $recipient, NotificationMessage $message): string
    {
        if (trim($recipient) === '') {
            throw new DeliveryFailedException('The recipient is empty.');
        }

        Log::info('notification.log_driver_delivered', [
            'event' => 'notification.log_driver_delivered',
            'subject' => $message->subject,
            'channel' => $message->channel->value,
            'entity_type' => $message->entityType,
            'entity_id' => $message->entityId,
            'delivery_id' => $message->deliveryId,
        ]);

        return 'log';
    }
}
