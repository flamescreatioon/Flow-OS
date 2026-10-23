<?php

namespace Database\Factories;

use App\Models\AuditEvent;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Ramsey\Uuid\Uuid;

/**
 * @extends Factory<AuditEvent>
 */
class AuditEventFactory extends Factory
{
    protected $model = AuditEvent::class;

    public function definition(): array
    {
        return [
            'id' => Uuid::uuid7()->toString(),
            'organization_id' => Organization::factory(),
            'actor_user_id' => User::factory(),
            'event_type' => 'organization.created',
            'entity_type' => 'organization',
            'entity_id' => Uuid::uuid7()->toString(),
            'metadata' => [],
            'created_at' => now(),
        ];
    }
}
