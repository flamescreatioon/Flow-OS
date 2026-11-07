<?php

namespace App\Application\Notifications;

use App\Enums\NotificationChannel;

/**
 * A message handed to a delivery channel.
 *
 * The content is built server-side from snapshot values; no client-supplied
 * body is ever rendered (ADR 0037). The context fields are safe identifiers
 * for structured logging — they never include the recipient or body.
 */
final class NotificationMessage
{
    public function __construct(
        public readonly string $subject,
        public readonly string $body,
        public readonly NotificationChannel $channel,
        public readonly string $entityType,
        public readonly string $entityId,
        public readonly string $deliveryId,
    ) {}
}
