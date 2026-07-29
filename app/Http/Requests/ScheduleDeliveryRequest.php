<?php

namespace App\Http\Requests;

use App\Enums\DeliveryType;
use App\Models\Delivery;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class ScheduleDeliveryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Delivery::class);
    }

    public function rules(): array
    {
        $order = $this->route('order');

        return [
            'type' => ['required', new Enum(DeliveryType::class)],
            'scheduled_at' => ['required', 'date', 'after_or_equal:now'],
            'delivery_zone_id' => [
                'nullable',
                'integer',
                Rule::exists('delivery_zones', 'id')->where('location_id', $order->location_id),
            ],
            'address' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:100'],
            'state' => ['required', 'string', 'size:2'],
            'zip' => ['required', 'string', 'max:10'],
            'driver_id' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id')->where('location_id', $order->location_id),
            ],
        ];
    }
}
