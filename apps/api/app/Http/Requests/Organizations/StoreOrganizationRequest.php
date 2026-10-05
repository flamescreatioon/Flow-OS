<?php

namespace App\Http\Requests\Organizations;

use Illuminate\Foundation\Http\FormRequest;

class StoreOrganizationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['name' => ['required', 'string', 'max:255'], 'legal_name' => ['nullable', 'string', 'max:255'], 'registration_number' => ['nullable', 'string', 'max:100'], 'tax_identification_number' => ['nullable', 'string', 'max:100'], 'phone' => ['nullable', 'string', 'max:50'], 'email' => ['nullable', 'email', 'max:255'], 'country_code' => ['nullable', 'regex:/^[A-Z]{2}$/'], 'currency_code' => ['nullable', 'regex:/^[A-Z]{3}$/'], 'timezone' => ['nullable', 'timezone']];
    }
}
