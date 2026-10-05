<?php

namespace App\Http\Requests\Organizations;

class UpdateOrganizationRequest extends StoreOrganizationRequest
{
    public function rules(): array
    {
        return array_map(fn ($rules) => array_merge(['sometimes'], $rules), parent::rules());
    }
}
