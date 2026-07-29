<?php

namespace App\Policies;

use App\Models\Service;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ServicePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Service $service): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Service $service): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, Service $service): bool
    {
        return false;
    }

    public function restore(User $user, Service $service): bool
    {
        return false;
    }

    public function forceDelete(User $user, Service $service): bool
    {
        return false;
    }
}
