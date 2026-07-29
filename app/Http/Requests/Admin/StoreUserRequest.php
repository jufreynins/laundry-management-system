<?php

namespace App\Http\Requests\Admin;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;
use App\Enums\UserRole;
use Illuminate\Validation\Rules\Password;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', User::class);
    }

    public function rules(): array
    {
        $password = Password::min(12)->max(64);
        if (!app()->runningUnitTests()) {
            $password = $password->uncompromised();
        }

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', $password],
            'role' => ['required', new Enum(UserRole::class)],
            'location_id' => ['nullable', 'integer', 'exists:locations,id'],
            'active' => ['boolean'],
        ];
    }
}
