<?php

namespace App\Http\Resources;

use App\Domain\Catalogue\ValueObjects\UnitPrice;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaxSettingsResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'organization_id' => $this->organization_id,
            'tax_enabled' => $this->tax_enabled,
            'tax_rate_percent' => $this->tax_rate_percent === null
                ? null
                : UnitPrice::of($this->tax_rate_percent)->toString(),
            'tax_mode' => $this->tax_mode?->value,
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
