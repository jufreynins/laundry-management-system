<?php

namespace App\Policies;

use App\Models\Delivery;
use App\Models\User;
use Illuminate\Auth\Access\Response;

use App\Enums\UserRole;

class DeliveryPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Delivery $delivery): bool
    {
        if ($user->hasRole(UserRole::DRIVER)) {
            return $delivery->driver_id === $user->id;
        }

        return $user->canAccessLocation($delivery->location);
    }

    public function create(User $user): bool
    {
        return $user->hasRole(UserRole::OWNER, UserRole::MANAGER, UserRole::CASHIER, UserRole::LAUNDRY_STAFF);
    }

    public function update(User $user, Delivery $delivery): bool
    {
        return $user->canAccessLocation($delivery->location) && $user->hasRole(UserRole::OWNER, UserRole::MANAGER);
    }

    public function updateStatus(User $user, Delivery $delivery): bool
    {
        if ($user->hasRole(UserRole::DRIVER)) {
            return $delivery->driver_id === $user->id;
        }

        return $user->canAccessLocation($delivery->location) && $user->hasRole(UserRole::OWNER, UserRole::MANAGER);
    }

    public function delete(User $user, Delivery $delivery): bool
    {
        return false;
    }

    public function restore(User $user, Delivery $delivery): bool
    {
        return false;
    }

    public function forceDelete(User $user, Delivery $delivery): bool
    {
        return false;
    }
}
