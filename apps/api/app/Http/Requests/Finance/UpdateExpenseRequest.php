<?php

namespace App\Http\Requests\Finance;

use App\Domain\Catalogue\ValueObjects\UnitPrice;
use App\Enums\PaymentMethod;
use App\Rules\CanonicalDecimal;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateExpenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'expense_category_id' => ['sometimes', 'uuid'],
            'branch_id' => ['sometimes', 'nullable', 'uuid'],
            'description' => ['sometimes', 'string', 'max:300'],
            'amount' => ['sometimes', new CanonicalDecimal(UnitPrice::SCALE)],
            'spent_on' => ['sometimes', 'date', 'before_or_equal:today'],
            'payee' => ['sometimes', 'nullable', 'string', 'max:200'],
            'payment_method' => ['sometimes', Rule::enum(PaymentMethod::class)],
            'note' => ['sometimes', 'nullable', 'string', 'max:500'],
        ];
    }
}
