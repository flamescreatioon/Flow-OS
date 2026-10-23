<?php

namespace App\Http\Requests\Roles;

use App\Models\Role;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $organization = $this->route('organization');
        $orgId = is_object($organization) ? $organization->id : $organization;

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (Role::isReservedSlug((string) $value)) {
                        $fail("The {$attribute} is a reserved system role name.");
                    }
                },
                Rule::unique('roles', 'name')->where('organization_id', $orgId),
            ],
            'description' => ['nullable', 'string'],
            'permission_ids' => ['array'],
            'permission_ids.*' => ['uuid', Rule::exists('permissions', 'id')],
        ];
    }
}
