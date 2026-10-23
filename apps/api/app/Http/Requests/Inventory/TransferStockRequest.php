<?php

namespace App\Http\Requests\Inventory;

use App\Domain\Shared\ValueObjects\Quantity;
use App\Rules\CanonicalDecimal;
use Illuminate\Foundation\Http\FormRequest;

class TransferStockRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_id' => ['required', 'uuid'],
            'from_location_id' => ['required', 'uuid'],
            'to_location_id' => ['required', 'uuid'],
            'quantity' => ['required', new CanonicalDecimal(Quantity::SCALE)],
            'reference' => ['nullable', 'string', 'max:100'],
            'note' => ['nullable', 'string', 'max:500'],
            'occurred_at' => ['nullable', 'date'],
        ];
    }
}
