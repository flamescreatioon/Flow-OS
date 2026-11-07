<?php

namespace App\Http\Requests\Invoicing;

use App\Domain\Catalogue\ValueObjects\UnitPrice;
use App\Domain\Shared\ValueObjects\Quantity;
use App\Rules\CanonicalDecimal;
use Illuminate\Foundation\Http\FormRequest;

class UpdateInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'customer_id' => ['sometimes', 'nullable', 'uuid'],
            'branch_id' => ['sometimes', 'nullable', 'uuid'],
            'due_on' => ['sometimes', 'nullable', 'date'],
            'note' => ['sometimes', 'nullable', 'string', 'max:500'],
            'lines' => ['sometimes', 'array', 'min:1'],
            'lines.*.product_id' => ['nullable', 'uuid'],
            'lines.*.description' => ['required_with:lines', 'string', 'max:300'],
            'lines.*.quantity' => ['required_with:lines', new CanonicalDecimal(Quantity::SCALE)],
            'lines.*.unit_price' => ['required_with:lines', new CanonicalDecimal(UnitPrice::SCALE)],
            'lines.*.discount' => ['nullable', new CanonicalDecimal(UnitPrice::SCALE)],
        ];
    }
}
