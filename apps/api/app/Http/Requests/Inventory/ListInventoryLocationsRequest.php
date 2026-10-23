<?php

namespace App\Http\Requests\Inventory;

use Illuminate\Foundation\Http\FormRequest;

class ListInventoryLocationsRequest extends FormRequest
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
            'branch_id' => ['sometimes', 'uuid'],
            'archived' => ['sometimes', 'in:true,false'],
            'q' => ['sometimes', 'string', 'max:255'],
            'sort' => ['sometimes', 'string', 'in:name,-name,code,-code,created_at,-created_at'],
        ];
    }

    public function perPage(int $default = 25): int
    {
        return (int) ($this->validated()['per_page'] ?? $default);
    }
}
