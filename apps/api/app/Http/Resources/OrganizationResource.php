<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrganizationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return ['id' => $this->id, 'name' => $this->name, 'slug' => $this->slug, 'legal_name' => $this->legal_name, 'registration_number' => $this->registration_number, 'tax_identification_number' => $this->tax_identification_number, 'phone' => $this->phone, 'email' => $this->email, 'country_code' => $this->country_code, 'currency_code' => $this->currency_code, 'timezone' => $this->timezone, 'status' => $this->status?->value, 'created_at' => $this->created_at?->toISOString(), 'updated_at' => $this->updated_at?->toISOString()];
    }
}
