<?php

namespace App\Policies;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Auth\Access\Response;

use App\Enums\UserRole;

class CustomerPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Customer $customer): bool
    {
        return $user->canAccessLocation($customer->location);
    }

    public function create(User $user): bool
    {
        return !$user->hasRole(UserRole::ACCOUNTANT);
    }

    public function update(User $user, Customer $customer): bool
    {
        return $user->canAccessLocation($customer->location) && !$user->hasRole(UserRole::ACCOUNTANT);
    }

    public function delete(User $user, Customer $customer): bool
    {
        return false;
    }

    public function restore(User $user, Customer $customer): bool
    {
        return false;
    }

    public function forceDelete(User $user, Customer $customer): bool
    {
        return false;
    }
}
