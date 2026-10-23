<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrganizationInvitationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'email' => $this->email,
            'state' => $this->state(),
            'expires_at' => $this->expires_at,
            'accepted_at' => $this->accepted_at,
            'declined_at' => $this->declined_at,
            'cancelled_at' => $this->cancelled_at,
            'created_at' => $this->created_at,
            'invited_by' => $this->whenLoaded('inviter', fn () => [
                'id' => $this->inviter?->id,
                'name' => $this->inviter?->name,
                'email' => $this->inviter?->email,
            ]),
        ];
    }
}
