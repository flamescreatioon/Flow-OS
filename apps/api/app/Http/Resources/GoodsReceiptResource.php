<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GoodsReceiptResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'organization_id' => $this->organization_id,
            'branch_id' => $this->branch_id,
            'supplier_id' => $this->supplier_id,
            'location_id' => $this->location_id,
            'location' => new InventoryLocationResource($this->whenLoaded('location')),
            // Snapshots: the supplier and product names at receipt time.
            'supplier_name' => $this->supplier_name,
            'receipt_number' => $this->receipt_number,
            'document_number' => $this->document_number,
            'status' => $this->status?->value,
            'received_on' => $this->received_on?->format('Y-m-d'),
            'note' => $this->note,
            'total_cost' => $this->totalCost()->toString(),
            'lines' => GoodsReceiptLineResource::collection($this->whenLoaded('lines')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
