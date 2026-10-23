<?php

namespace App\Http\Requests\Catalogue;

use App\Domain\Catalogue\ValueObjects\UnitPrice;
use App\Enums\ProductType;
use App\Rules\CanonicalDecimal;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:200'],
            'sku' => ['nullable', 'string', 'max:100'],
            'type' => ['required', Rule::enum(ProductType::class)],
            'price' => ['required', new CanonicalDecimal(UnitPrice::SCALE)],
            'tax_code' => ['nullable', 'string', 'max:50'],
            'unit_of_measure_id' => ['required', 'uuid'],
            'category_id' => ['nullable', 'uuid'],
        ];
    }
}
