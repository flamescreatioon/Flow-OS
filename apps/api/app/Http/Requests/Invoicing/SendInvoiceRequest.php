<?php

namespace App\Http\Requests\Invoicing;

use App\Enums\NotificationChannel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SendInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'channel' => ['required', Rule::enum(NotificationChannel::class)],
            // The recipient defaults from the attached customer's contact
            // details when omitted; an explicit value overrides it.
            'recipient' => ['nullable', 'string', 'max:255'],
        ];
    }
}
