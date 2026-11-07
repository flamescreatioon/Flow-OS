<?php

namespace App\Http\Requests\Invoicing;

use App\Domain\Catalogue\ValueObjects\UnitPrice;
use App\Domain\Shared\ValueObjects\Quantity;
use App\Rules\CanonicalDecimal;
use Illuminate\Foundation\Http\FormRequest;

class CreateInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'customer_id' => ['nullable', 'uuid'],
            'branch_id' => ['nullable', 'uuid'],
            'due_on' => ['nullable', 'date'],
            'note' => ['nullable', 'string', 'max:500'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.product_id' => ['nullable', 'uuid'],
            'lines.*.description' => ['required', 'string', 'max:300'],
            'lines.*.quantity' => ['required', new CanonicalDecimal(Quantity::SCALE)],
            'lines.*.unit_price' => ['required', new CanonicalDecimal(UnitPrice::SCALE)],
            'lines.*.discount' => ['nullable', new CanonicalDecimal(UnitPrice::SCALE)],
        ];
    }
}
