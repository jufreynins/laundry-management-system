<?php

namespace App\Http\Requests;

use App\Models\Expense;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreExpenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Expense::class);
    }

    public function rules(): array
    {
        $locationId = $this->input('location_id');

        return [
            'location_id' => ['required', 'integer', 'exists:locations,id'],
            'expense_category_id' => ['required', 'integer', 'exists:expense_categories,id'],
            'supplier_id' => [
                'nullable',
                'integer',
                Rule::exists('suppliers', 'id')->where(fn ($q) => $q->where('location_id', $locationId)->orWhereNull('location_id')),
            ],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:999999.99'],
            'description' => ['required', 'string', 'max:255'],
            'expense_date' => ['required', 'date', 'before_or_equal:today'],
        ];
    }
}
