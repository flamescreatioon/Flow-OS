<?php

namespace App\Http\Requests\Inventory;

use App\Enums\StockMovementType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListStockMovementsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'product_id' => ['sometimes', 'uuid'],
            'location_id' => ['sometimes', 'uuid'],
            'type' => ['sometimes', Rule::enum(StockMovementType::class)],
        ];
    }

    public function perPage(int $default = 25): int
    {
        return (int) ($this->validated()['per_page'] ?? $default);
    }
}
