<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class VerifyMfaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'challenge' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:64'],
        ];
    }
}
