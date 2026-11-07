<?php

namespace App\Http\Requests\Invoicing;

use App\Domain\Catalogue\ValueObjects\UnitPrice;
use App\Enums\TaxMode;
use App\Rules\CanonicalDecimal;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTaxSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tax_enabled' => ['required', 'boolean'],
            'tax_rate_percent' => ['nullable', new CanonicalDecimal(UnitPrice::SCALE)],
            'tax_mode' => ['sometimes', Rule::enum(TaxMode::class)],
        ];
    }
}
