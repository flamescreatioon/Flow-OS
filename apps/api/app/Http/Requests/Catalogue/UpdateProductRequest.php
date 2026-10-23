<?php

namespace App\Http\Requests\Catalogue;

use App\Domain\Catalogue\ValueObjects\UnitPrice;
use App\Enums\ProductType;
use App\Rules\CanonicalDecimal;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:200'],
            'sku' => ['sometimes', 'nullable', 'string', 'max:100'],
            'type' => ['sometimes', Rule::enum(ProductType::class)],
            'price' => ['sometimes', new CanonicalDecimal(UnitPrice::SCALE)],
            'tax_code' => ['sometimes', 'nullable', 'string', 'max:50'],
            'unit_of_measure_id' => ['sometimes', 'uuid'],
            'category_id' => ['sometimes', 'nullable', 'uuid'],
        ];
    }
}
