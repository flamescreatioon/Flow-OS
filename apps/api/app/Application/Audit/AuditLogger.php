<?php

namespace App\Application\Audit;

use App\Models\AuditEvent;
use App\Models\Organization;
use App\Models\User;

final class AuditLogger
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function record(Organization $organization, ?User $actor, string $eventType, string $entityType, ?string $entityId, array $metadata = []): AuditEvent
    {
        $event = new AuditEvent;
        $event->forceFill([
            'organization_id' => $organization->id,
            'actor_user_id' => $actor?->getKey(),
            'event_type' => $eventType,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'metadata' => $this->allowlist($metadata),
            'created_at' => now(),
        ])->save();

        return $event;
    }

    /**
     * Keep audit metadata to safe scalar identifiers only; never dump payloads.
     *
     * @param  array<string, mixed>  $metadata
     * @return array<string, mixed>
     */
    private function allowlist(array $metadata): array
    {
        $allowed = [];
        foreach ($metadata as $key => $value) {
            if (! is_string($key) || $key === '') {
                continue;
            }
            if (is_scalar($value) || $value === null) {
                $allowed[$key] = $value;
            }
        }

        return $allowed;
    }
}
