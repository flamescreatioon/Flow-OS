<?php

namespace App\Http\Requests\Invoicing;

use Illuminate\Foundation\Http\FormRequest;

class ListCustomersRequest extends FormRequest
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
            'archived' => ['sometimes', 'in:true,false'],
            'q' => ['sometimes', 'string', 'max:255'],
            'sort' => ['sometimes', 'string', 'in:name,-name,created_at,-created_at'],
        ];
    }

    public function perPage(int $default = 25): int
    {
        return (int) ($this->validated()['per_page'] ?? $default);
    }
}
