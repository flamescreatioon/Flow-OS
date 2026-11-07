<?php

namespace App\Http\Requests\Finance;

use App\Enums\ExpenseStatus;
use App\Enums\PaymentMethod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListExpensesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'expense_category_id' => ['sometimes', 'uuid'],
            'status' => ['sometimes', Rule::enum(ExpenseStatus::class)],
            'payment_method' => ['sometimes', Rule::enum(PaymentMethod::class)],
        ];
    }

    public function perPage(int $default = 25): int
    {
        return (int) ($this->validated()['per_page'] ?? $default);
    }
}
