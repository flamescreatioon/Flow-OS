<?php

namespace App\Http\Resources;

use App\Domain\Catalogue\ValueObjects\UnitPrice;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'organization_id' => $this->organization_id,
            'branch_id' => $this->branch_id,
            'customer_id' => $this->customer_id,
            'customer_name' => $this->customer_name,
            'invoice_number' => $this->invoice_number,
            'document_number' => $this->document_number,
            'status' => $this->status?->value,
            'issued_at' => $this->issued_at?->toISOString(),
            'voided_at' => $this->voided_at?->toISOString(),
            'due_on' => $this->due_on?->format('Y-m-d'),
            'currency' => $this->currency,
            // The tax snapshot at issue time; null on drafts and when tax is
            // disabled.
            'tax_rate_percent' => $this->tax_rate_percent === null
                ? null
                : UnitPrice::of($this->tax_rate_percent)->toString(),
            'tax_mode' => $this->tax_mode?->value,
            'subtotal' => UnitPrice::of($this->subtotal)->toString(),
            'discount_total' => UnitPrice::of($this->discount_total)->toString(),
            'tax_total' => UnitPrice::of($this->tax_total)->toString(),
            'grand_total' => UnitPrice::of($this->grand_total)->toString(),
            'note' => $this->note,
            'lines' => InvoiceLineResource::collection($this->whenLoaded('lines')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
