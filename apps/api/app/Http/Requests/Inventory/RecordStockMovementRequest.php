<?php

namespace App\Http\Requests\Inventory;

use App\Domain\Catalogue\ValueObjects\UnitPrice;
use App\Domain\Shared\ValueObjects\Quantity;
use App\Enums\StockMovementType;
use App\Rules\CanonicalDecimal;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RecordStockMovementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_id' => ['required', 'uuid'],
            'location_id' => ['required', 'uuid'],
            'type' => ['required', Rule::enum(StockMovementType::class)],
            'quantity' => ['required', new CanonicalDecimal(Quantity::SCALE)],
            'unit_cost' => ['nullable', new CanonicalDecimal(UnitPrice::SCALE)],
            'reference' => ['nullable', 'string', 'max:100'],
            'note' => ['nullable', 'string', 'max:500'],
            'occurred_at' => ['nullable', 'date'],
            'corrects_movement_id' => ['nullable', 'uuid'],
        ];
    }
}
