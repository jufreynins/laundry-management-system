<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RefundPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('refund', $this->route('payment'));
    }

    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'min:0.01', 'max:99999.99'],
            'reason' => ['required', 'string', 'max:1000'],
        ];
    }
}
