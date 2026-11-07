<?php

namespace App\Application\Notifications;

use App\Mail\GenericMessageMailable;
use Illuminate\Support\Facades\Mail;
use RuntimeException;

/**
 * Email delivery through Laravel Mail (SMTP in production, Mailpit locally).
 */
final class MailChannel implements DeliveryChannel
{
    public function send(string $recipient, NotificationMessage $message): string
    {
        if (! filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('The email recipient is not a valid address.');
        }

        Mail::send(
            (new GenericMessageMailable($message->subject, $message->body))->to($recipient)
        );

        return 'mail';
    }
}
