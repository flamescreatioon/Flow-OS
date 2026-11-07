<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationDeliveryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'organization_id' => $this->organization_id,
            'entity_type' => $this->entity_type,
            'entity_id' => $this->entity_id,
            'channel' => $this->channel?->value,
            'driver' => $this->driver,
            'recipient' => $this->recipient,
            'status' => $this->status?->value,
            'error_message' => $this->error_message,
            'sent_at' => $this->sent_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
