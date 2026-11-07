<?php

namespace Database\Factories;

use App\Models\NotificationDelivery;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;
use Ramsey\Uuid\Uuid;

/**
 * @extends Factory<NotificationDelivery>
 */
class NotificationDeliveryFactory extends Factory
{
    protected $model = NotificationDelivery::class;

    public function definition(): array
    {
        return [
            'id' => Uuid::uuid7()->toString(),
            'organization_id' => Organization::factory(),
            'entity_type' => 'invoice',
            'entity_id' => Uuid::uuid7()->toString(),
            'channel' => 'email',
            'driver' => 'mail',
            'recipient' => fake()->safeEmail(),
            'status' => 'queued',
            'error_message' => null,
            'sent_at' => null,
        ];
    }

    public function sent(): static
    {
        return $this->state(fn (): array => [
            'status' => 'sent',
            'sent_at' => now(),
        ]);
    }

    public function failed(string $message = 'The delivery could not be sent.'): static
    {
        return $this->state(fn (): array => [
            'status' => 'failed',
            'error_message' => $message,
        ]);
    }
}
