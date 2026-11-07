<?php

namespace App\Http\Requests\Finance;

use Illuminate\Foundation\Http\FormRequest;

class ListExpenseCategoriesRequest extends FormRequest
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
        ];
    }

    public function perPage(int $default = 100): int
    {
        return (int) ($this->validated()['per_page'] ?? $default);
    }
}
