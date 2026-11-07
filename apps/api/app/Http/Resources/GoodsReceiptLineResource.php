<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GoodsReceiptLineResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'goods_receipt_id' => $this->goods_receipt_id,
            'product_id' => $this->product_id,
            // Exact decimal string in thousandths of a unit of measure.
            'quantity' => $this->quantity()->toString(),
            'unit_cost' => $this->unitCost()->toString(),
            'line_total' => $this->lineTotal()->toString(),
            'product' => new ProductResource($this->whenLoaded('product')),
        ];
    }
}
