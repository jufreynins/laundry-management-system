<?php

namespace App\Http\Requests;

use App\Enums\DeliveryStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class UpdateDeliveryStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('updateStatus', $this->route('delivery'));
    }

    public function rules(): array
    {
        return [
            'status' => ['required', new Enum(DeliveryStatus::class)],
            'proof_notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
