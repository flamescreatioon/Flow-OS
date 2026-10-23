<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AuditEventResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'event_type' => $this->event_type,
            'entity_type' => $this->entity_type,
            'entity_id' => $this->entity_id,
            'actor_user_id' => $this->actor_user_id,
            'metadata' => $this->metadata ?? [],
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
