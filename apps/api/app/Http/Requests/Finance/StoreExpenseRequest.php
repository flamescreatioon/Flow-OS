<?php

namespace App\Http\Requests\Finance;

use App\Domain\Catalogue\ValueObjects\UnitPrice;
use App\Enums\PaymentMethod;
use App\Rules\CanonicalDecimal;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreExpenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'expense_category_id' => ['required', 'uuid'],
            'branch_id' => ['nullable', 'uuid'],
            'description' => ['required', 'string', 'max:300'],
            'amount' => ['required', new CanonicalDecimal(UnitPrice::SCALE)],
            'spent_on' => ['nullable', 'date', 'before_or_equal:today'],
            'payee' => ['nullable', 'string', 'max:200'],
            'payment_method' => ['sometimes', Rule::enum(PaymentMethod::class)],
            'note' => ['nullable', 'string', 'max:500'],
        ];
    }
}
