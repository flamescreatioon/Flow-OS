<?php

namespace App\Jobs;

use App\Application\Notifications\ChannelResolver;
use App\Application\Notifications\DeliveryFailedException;
use App\Application\Notifications\NotificationMessage;
use App\Enums\NotificationDeliveryStatus;
use App\Models\NotificationDelivery;
use App\Models\Organization;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Performs the transport call for one tracked delivery (ADR 0037).
 *
 * The delivery row was created with the send request; this job resolves it
 * to sent or failed. Delivery state never feeds back into business
 * correctness, so a transport failure is recorded and logged, not rethrown.
 *
 * Tenant safety: this job deliberately does not implement Spatie's
 * TenantAware marker, because tenant context in this codebase is explicit —
 * no current tenant is bound on API routes. Instead the job carries the
 * organization id captured at dispatch and re-resolves it in the worker,
 * failing loudly when the organization is missing.
 */
final class DeliverInvoiceNotification implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly string $deliveryId,
        public readonly string $organizationId,
        public readonly string $subject,
        public readonly string $body,
    ) {}

    public function handle(ChannelResolver $resolver): void
    {
        $delivery = NotificationDelivery::query()->find($this->deliveryId);

        if (! $delivery || $delivery->status !== NotificationDeliveryStatus::Queued) {
            return;
        }

        $organization = Organization::query()->find($this->organizationId);

        if (! $organization) {
            // Fail loudly rather than deliver outside a tenant context.
            throw new RuntimeException('The delivery organization no longer exists.');
        }

        try {
            $driver = $resolver->resolve($delivery->channel)->send(
                $delivery->recipient,
                new NotificationMessage(
                    $this->subject,
                    $this->body,
                    $delivery->channel,
                    $delivery->entity_type,
                    $delivery->entity_id,
                    $delivery->id,
                )
            );
        } catch (DeliveryFailedException $exception) {
            $this->markFailed($delivery, $exception->getMessage());

            return;
        } catch (RuntimeException $exception) {
            $this->markFailed($delivery, 'The delivery could not be sent.');

            return;
        }

        $delivery->forceFill([
            'status' => NotificationDeliveryStatus::Sent->value,
            'driver' => $driver,
            'sent_at' => now(),
        ])->save();

        Log::info('notification.delivered', [
            'event' => 'notification.delivered',
            'delivery_id' => $delivery->id,
            'entity_type' => $delivery->entity_type,
            'entity_id' => $delivery->entity_id,
            'channel' => $delivery->channel->value,
            'driver' => $driver,
        ]);
    }

    private function markFailed(NotificationDelivery $delivery, string $message): void
    {
        $delivery->forceFill([
            'status' => NotificationDeliveryStatus::Failed->value,
            'error_message' => $message,
        ])->save();

        Log::warning('notification.delivery_failed', [
            'event' => 'notification.delivery_failed',
            'delivery_id' => $delivery->id,
            'entity_type' => $delivery->entity_type,
            'entity_id' => $delivery->entity_id,
            'channel' => $delivery->channel->value,
        ]);
    }
}
