<?php

namespace App\Http\Requests\Admin;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('user'));
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->route('user'))],
            'role' => ['required', new Enum(UserRole::class)],
            'location_id' => ['nullable', 'integer', 'exists:locations,id'],
            'active' => ['boolean'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $actor = $this->user();

            if ($actor->hasRole(UserRole::OWNER)) {
                return;
            }

            // A Manager cannot promote anyone to Owner/Manager (but editing
            // their own profile without changing their existing role is fine),
            // and cannot move a user to a location outside their own.
            $targetUser = $this->route('user');
            $requestedRole = $this->input('role');
            $roleIsChanging = $targetUser && $requestedRole !== $targetUser->role->value;
            if ($roleIsChanging && in_array($requestedRole, [UserRole::OWNER->value, UserRole::MANAGER->value], true)) {
                $validator->errors()->add('role', 'Only an Owner can assign Owner or Manager roles.');
            }

            $scopedLocationId = $actor->scopedLocationId();
            if ($scopedLocationId !== null && (int) $this->input('location_id') !== $scopedLocationId) {
                $validator->errors()->add('location_id', 'You can only manage users at your own location.');
            }
        });
    }
}
