<?php

namespace App\Application\Notifications;

/**
 * A transport over which notifications are delivered (ADR 0037).
 *
 * Implementations own one driver. They receive a validated recipient and a
 * server-built message, and either deliver it or throw a
 * DeliveryFailedException whose message is safe to store on a delivery row.
 */
interface DeliveryChannel
{
    /**
     * Deliver the message and return the driver name that handled it.
     */
    public function send(string $recipient, NotificationMessage $message): string;
}
