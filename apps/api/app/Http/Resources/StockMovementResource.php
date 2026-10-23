<?php

namespace App\Http\Resources;

use App\Domain\Catalogue\ValueObjects\UnitPrice;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StockMovementResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'organization_id' => $this->organization_id,
            'product_id' => $this->product_id,
            'location_id' => $this->location_id,
            'type' => $this->type?->value,
            // Exact decimal string in thousandths of a unit of measure.
            'quantity' => $this->quantity()->toString(),
            'unit_cost' => $this->unit_cost === null ? null : UnitPrice::of($this->unit_cost)->toString(),
            'reference' => $this->reference,
            'note' => $this->note,
            'occurred_at' => $this->occurred_at?->toISOString(),
            'corrects_movement_id' => $this->corrects_movement_id,
            'transfer_movement_id' => $this->transfer_movement_id,
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
