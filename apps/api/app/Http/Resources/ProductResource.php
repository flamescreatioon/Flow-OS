<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'organization_id' => $this->organization_id,
            'name' => $this->name,
            'sku' => $this->sku,
            'type' => $this->type?->value,
            // Exact decimal string. The value is authoritative in the
            // organization currency.
            'price' => $this->unitPrice()->toString(),
            'tax_code' => $this->tax_code,
            'unit_of_measure' => new UnitOfMeasureResource($this->whenLoaded('unitOfMeasure')),
            'category' => new CategoryResource($this->whenLoaded('category')),
            'archived_at' => $this->archived_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
