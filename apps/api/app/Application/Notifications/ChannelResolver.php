<?php

namespace App\Application\Notifications;

use App\Enums\NotificationChannel;
use RuntimeException;

/**
 * Resolves the configured driver for a notification channel (ADR 0037).
 *
 * Email defaults to Laravel Mail. SMS and WhatsApp default to the log
 * driver until a provider is selected; selecting one is a later ADR plus a
 * DeliveryChannel adapter, not a re-architecture.
 */
final class ChannelResolver
{
    /**
     * The configured driver name for a channel, e.g. "mail" or "log".
     */
    public function driverFor(NotificationChannel $channel): string
    {
        $driver = (string) config("notifications.channels.{$channel->value}.driver", 'log');

        if (! in_array($driver, ['mail', 'log'], true)) {
            throw new RuntimeException("Unknown notification driver [{$driver}].");
        }

        if ($driver === 'mail' && $channel !== NotificationChannel::Email) {
            throw new RuntimeException('The mail driver only delivers the email channel.');
        }

        return $driver;
    }

    public function resolve(NotificationChannel $channel): DeliveryChannel
    {
        return match ($this->driverFor($channel)) {
            'mail' => app(MailChannel::class),
            'log' => app(LogChannel::class),
        };
    }
}
