<?php

namespace App\Http\Requests\Catalogue;

use Illuminate\Foundation\Http\FormRequest;

class StoreUnitOfMeasureRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:20'],
            'name' => ['required', 'string', 'max:100'],
            'precision' => ['sometimes', 'integer', 'min:0', 'max:3'],
        ];
    }
}
