<?php

namespace App\Http\Requests;

use App\Models\InventoryItem;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreInventoryItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', InventoryItem::class);
    }

    public function rules(): array
    {
        $locationId = $this->input('location_id');

        return [
            'location_id' => ['required', 'integer', 'exists:locations,id'],
            'supplier_id' => [
                'nullable',
                'integer',
                Rule::exists('suppliers', 'id')->where(fn ($q) => $q->where('location_id', $locationId)->orWhereNull('location_id')),
            ],
            'name' => ['required', 'string', 'max:255'],
            'unit' => ['required', 'string', 'max:30'],
            'current_quantity' => ['required', 'numeric', 'min:0', 'max:999999.99'],
            'reorder_threshold' => ['required', 'numeric', 'min:0', 'max:999999.99'],
            'cost_per_unit' => ['nullable', 'numeric', 'min:0', 'max:99999.99'],
        ];
    }
}
