<?php

namespace App\Policies;

use App\Models\Location;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class LocationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, Location $location): bool
    {
        return $user->canAccessLocation($location);
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Location $location): bool
    {
        return $user->canAccessLocation($location) && $user->isAdmin();
    }

    public function delete(User $user, Location $location): bool
    {
        return false;
    }

    public function restore(User $user, Location $location): bool
    {
        return false;
    }

    public function forceDelete(User $user, Location $location): bool
    {
        return false;
    }
}
