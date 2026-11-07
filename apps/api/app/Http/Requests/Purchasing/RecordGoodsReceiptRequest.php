<?php

namespace App\Http\Requests\Purchasing;

use App\Domain\Catalogue\ValueObjects\UnitPrice;
use App\Domain\Shared\ValueObjects\Quantity;
use App\Rules\CanonicalDecimal;
use Illuminate\Foundation\Http\FormRequest;

class RecordGoodsReceiptRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'supplier_id' => ['nullable', 'uuid'],
            'branch_id' => ['nullable', 'uuid'],
            'location_id' => ['required', 'uuid'],
            'received_on' => ['nullable', 'date', 'before_or_equal:today'],
            'note' => ['nullable', 'string', 'max:500'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.product_id' => ['required', 'uuid'],
            'lines.*.quantity' => ['required', new CanonicalDecimal(Quantity::SCALE)],
            'lines.*.unit_cost' => ['required', new CanonicalDecimal(UnitPrice::SCALE)],
        ];
    }
}
