<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * A plain-text transport for platform notifications (ADR 0037).
 *
 * The body is built server-side from snapshot values; the subject and body
 * are carried verbatim, and the recipient is attached by the sending channel.
 * Queueing is owned by the delivery job, not this mailable.
 */
final class GenericMessageMailable extends Mailable
{
    public function __construct(
        public readonly string $messageSubject,
        public readonly string $messageBody,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->messageSubject);
    }

    public function content(): Content
    {
        return new Content(
            text: 'mail.generic-text',
            with: ['body' => $this->messageBody],
        );
    }
}
