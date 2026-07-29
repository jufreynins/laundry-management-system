<?php

namespace App\Http\Requests;

use App\Enums\PaymentMethod;
use App\Models\Payment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Payment::class);
    }

    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'min:0.01', 'max:99999.99'],
            // online_card is deliberately excluded: it can only be created via
            // the hosted-checkout flow (OnlinePaymentController), never recorded
            // manually as an already-completed payment.
            'method' => ['required', Rule::in([PaymentMethod::CASH->value, PaymentMethod::EXTERNAL->value])],
            'reference_note' => ['nullable', 'string', 'max:255'],
            'idempotency_key' => ['required', 'string', 'max:64'],
        ];
    }
}
