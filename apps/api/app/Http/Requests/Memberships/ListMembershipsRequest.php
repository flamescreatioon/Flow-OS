<?php

namespace App\Http\Requests\Memberships;

use App\Enums\MembershipStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListMembershipsRequest extends FormRequest
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
            'status' => ['sometimes', Rule::enum(MembershipStatus::class)],
            'q' => ['sometimes', 'string', 'max:255'],
        ];
    }

    public function perPage(int $default = 25): int
    {
        return (int) ($this->validated()['per_page'] ?? $default);
    }
}
