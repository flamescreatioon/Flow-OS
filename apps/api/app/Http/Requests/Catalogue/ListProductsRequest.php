<?php

namespace App\Http\Requests\Catalogue;

use App\Enums\ProductType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListProductsRequest extends FormRequest
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
            'type' => ['sometimes', Rule::enum(ProductType::class)],
            'category_id' => ['sometimes', 'uuid'],
            'archived' => ['sometimes', 'in:true,false,all'],
            'q' => ['sometimes', 'string', 'max:255'],
            'sort' => ['sometimes', 'string', 'in:name,-name,sku,-sku,price,-price,created_at,-created_at'],
        ];
    }

    public function perPage(int $default = 25): int
    {
        return (int) ($this->validated()['per_page'] ?? $default);
    }
}
