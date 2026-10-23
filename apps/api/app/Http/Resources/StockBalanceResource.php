<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StockBalanceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'organization_id' => $this->organization_id,
            'product_id' => $this->product_id,
            'location_id' => $this->location_id,
            // Exact decimal string in thousandths of a unit of measure.
            'quantity' => $this->quantity()->toString(),
            'product' => new ProductResource($this->whenLoaded('product')),
            'location' => new InventoryLocationResource($this->whenLoaded('location')),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
