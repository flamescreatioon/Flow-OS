<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceLineResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'invoice_id' => $this->invoice_id,
            'product_id' => $this->product_id,
            'description' => $this->description,
            // Exact decimal string in thousandths of a unit of measure.
            'quantity' => $this->quantity()->toString(),
            'unit_price' => $this->unitPrice()->toString(),
            'discount' => $this->discount()->toString(),
            'tax_amount' => $this->taxAmount()->toString(),
            'line_total' => $this->lineTotal()->toString(),
        ];
    }
}
