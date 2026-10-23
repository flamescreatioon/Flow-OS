<?php

namespace App\Notifications;

use App\Models\OrganizationInvitation;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class OrganizationInvitationNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly OrganizationInvitation $invitation,
        private readonly string $token,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = rtrim((string) config('app.frontend_url'), '/').'/invitations/'.$this->token;

        return (new MailMessage)
            ->subject('Invitation to '.($this->invitation->organization?->name ?? config('app.name')))
            ->greeting('You have been invited to FlowOS')
            ->line('You have been invited to join '.$this->invitation->organization?->name.'.')
            ->line('This invitation expires '.$this->invitation->expires_at?->toDayDateTimeString().'.')
            ->action('View invitation', $url)
            ->line('If you were not expecting this invitation, you can safely ignore this email.');
    }
}
