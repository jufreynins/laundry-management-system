<?php

namespace App\Http\Requests;

use App\Enums\IntakeChannel;
use App\Models\Customer;
use App\Models\Order;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Order::class);
    }

    public function rules(): array
    {
        return [
            'customer_id' => ['required', 'integer', 'exists:customers,id'],
            'location_id' => ['required', 'integer', 'exists:locations,id'],
            'intake_channel' => ['required', new Enum(IntakeChannel::class)],
            'promised_at' => ['nullable', 'date', 'after_or_equal:now'],
            'rush' => ['boolean'],
            'assigned_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'weight_lbs' => ['nullable', 'numeric', 'min:0', 'max:9999'],
            'item_count' => ['nullable', 'integer', 'min:0', 'max:99999'],
            'bag_count' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'stain_notes' => ['nullable', 'string', 'max:2000'],
            'customer_instructions' => ['nullable', 'string', 'max:2000'],
            'internal_notes' => ['nullable', 'string', 'max:2000'],
            'discount_amount' => ['nullable', 'numeric', 'min:0', 'max:99999.99'],
            'tip_amount' => ['nullable', 'numeric', 'min:0', 'max:99999.99'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.service_id' => ['required', 'integer', 'exists:services,id'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01', 'max:99999'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $customer = Customer::find($this->input('customer_id'));
            if ($customer && (int) $customer->location_id !== (int) $this->input('location_id')) {
                $validator->errors()->add('customer_id', 'The selected customer does not belong to the selected location.');
            }
        });
    }
}
