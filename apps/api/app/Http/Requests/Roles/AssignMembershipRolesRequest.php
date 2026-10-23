<?php

namespace App\Http\Requests\Roles;

use Illuminate\Foundation\Http\FormRequest;

class AssignMembershipRolesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['role_ids' => ['required', 'array'], 'role_ids.*' => ['uuid', 'distinct']];
    }
}
