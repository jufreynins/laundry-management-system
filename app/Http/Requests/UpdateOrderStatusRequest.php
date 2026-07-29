<?php

namespace App\Http\Requests;

use App\Enums\OrderStatus;
use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class UpdateOrderStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        $order = $this->route('order');

        if (!$this->user()->can('update', $order)) {
            return false;
        }

        if ($this->boolean('override')) {
            return $this->user()->hasRole(UserRole::OWNER);
        }

        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', new Enum(OrderStatus::class)],
            'override' => ['boolean'],
            'confirm_override' => ['accepted_if:override,1'],
            'reason' => ['required_if:override,1', 'nullable', 'string', 'max:1000'],
        ];
    }
}
