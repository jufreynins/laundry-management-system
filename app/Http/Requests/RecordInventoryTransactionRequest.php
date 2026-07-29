<?php

namespace App\Http\Requests;

use App\Enums\InventoryTransactionType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class RecordInventoryTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('recordTransaction', $this->route('inventoryItem'));
    }

    public function rules(): array
    {
        return [
            'type' => ['required', new Enum(InventoryTransactionType::class)],
            'quantity' => ['required', 'numeric', 'max:999999.99'],
            'reason' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
