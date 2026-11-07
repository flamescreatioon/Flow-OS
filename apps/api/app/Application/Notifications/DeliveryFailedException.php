<?php

namespace App\Application\Notifications;

use RuntimeException;

/**
 * A transport could not deliver the message.
 *
 * The message text must be safe to store on a delivery row: no credentials,
 * no provider secrets, no raw HTTP payloads.
 */
final class DeliveryFailedException extends RuntimeException {}
