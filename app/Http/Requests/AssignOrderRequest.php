<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AssignOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('order'));
    }

    public function rules(): array
    {
        $order = $this->route('order');

        return [
            'assigned_user_id' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id')->where('location_id', $order->location_id),
            ],
        ];
    }
}
