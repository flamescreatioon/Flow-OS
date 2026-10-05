<?php

namespace App\Http\Requests\Branches;

use Illuminate\Validation\Rule;

class UpdateBranchRequest extends StoreBranchRequest
{
    public function rules(): array
    {
        $organizationId = $this->route('organization')?->id ?? $this->route('organization_id');
        $branchId = $this->route('branch')?->id ?? $this->route('branch_id');

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'code' => ['sometimes', 'required', 'string', 'max:50', Rule::unique('branches', 'code')->where('organization_id', $organizationId)->ignore($branchId)],
            'phone' => ['sometimes', 'nullable', 'string', 'max:50'],
            'email' => ['sometimes', 'nullable', 'email', 'max:255'],
            'address_line_1' => ['sometimes', 'nullable', 'string', 'max:255'],
            'address_line_2' => ['sometimes', 'nullable', 'string', 'max:255'],
            'city' => ['sometimes', 'nullable', 'string', 'max:100'],
            'state' => ['sometimes', 'nullable', 'string', 'max:100'],
            'country_code' => ['sometimes', 'nullable', 'regex:/^[A-Z]{2}$/'],
        ];
    }
}
