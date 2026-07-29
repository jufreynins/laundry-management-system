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

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $actor = $this->user();

            // Only Owner may create another Owner or Manager account —
            // a location Manager cannot escalate a new user to their own
            // level or above.
            if (!$actor->hasRole(UserRole::OWNER)) {
                $requestedRole = $this->input('role');
                if (in_array($requestedRole, [UserRole::OWNER->value, UserRole::MANAGER->value], true)) {
                    $validator->errors()->add('role', 'Only an Owner can create Owner or Manager accounts.');
                }
            }

            // A non-Owner is confined to creating users at their own location.
            $scopedLocationId = $actor->scopedLocationId();
            if ($scopedLocationId !== null && (int) $this->input('location_id') !== $scopedLocationId) {
                $validator->errors()->add('location_id', 'You can only create users for your own location.');
            }
        });
    }
}
