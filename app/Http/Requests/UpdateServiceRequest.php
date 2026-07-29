<?php

namespace App\Http\Requests;

use App\Enums\PricingType;
use App\Enums\ServiceCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class UpdateServiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('service'));
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'category' => ['required', new Enum(ServiceCategory::class)],
            'pricing_type' => ['required', new Enum(PricingType::class)],
            'base_price' => ['required', 'numeric', 'min:0', 'max:99999.99'],
            'minimum_charge' => ['nullable', 'numeric', 'min:0', 'max:99999.99'],
            'taxable' => ['boolean'],
            'rush_eligible' => ['boolean'],
            'estimated_duration_minutes' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'active' => ['boolean'],
        ];
    }
}
