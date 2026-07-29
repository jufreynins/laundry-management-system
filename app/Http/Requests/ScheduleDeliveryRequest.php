<?php

namespace App\Http\Requests;

use App\Enums\DeliveryType;
use App\Models\Delivery;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class ScheduleDeliveryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Delivery::class);
    }

    public function rules(): array
    {
        return [
            'type' => ['required', new Enum(DeliveryType::class)],
            'scheduled_at' => ['required', 'date', 'after_or_equal:now'],
            'delivery_zone_id' => ['nullable', 'integer', 'exists:delivery_zones,id'],
            'address' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:100'],
            'state' => ['required', 'string', 'size:2'],
            'zip' => ['required', 'string', 'max:10'],
            'driver_id' => ['nullable', 'integer', 'exists:users,id'],
        ];
    }
}
