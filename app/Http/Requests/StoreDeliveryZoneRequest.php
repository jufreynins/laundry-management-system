<?php

namespace App\Http\Requests;

use App\Models\DeliveryZone;
use Illuminate\Foundation\Http\FormRequest;

class StoreDeliveryZoneRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', DeliveryZone::class);
    }

    public function rules(): array
    {
        return [
            'location_id' => ['required', 'integer', 'exists:locations,id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'fee' => ['required', 'numeric', 'min:0', 'max:9999.99'],
            'active' => ['boolean'],
        ];
    }
}
