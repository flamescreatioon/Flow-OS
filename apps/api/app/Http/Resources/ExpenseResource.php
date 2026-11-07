<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExpenseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'organization_id' => $this->organization_id,
            'branch_id' => $this->branch_id,
            'expense_category_id' => $this->expense_category_id,
            'expense_category' => new ExpenseCategoryResource($this->whenLoaded('category')),
            'expense_number' => $this->expense_number,
            'document_number' => $this->document_number,
            'status' => $this->status?->value,
            'voided_at' => $this->voided_at?->toISOString(),
            'description' => $this->description,
            'amount' => $this->amount()->toString(),
            'spent_on' => $this->spent_on?->format('Y-m-d'),
            'payee' => $this->payee,
            'payment_method' => $this->payment_method?->value,
            'note' => $this->note,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
