<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, User $model): bool
    {
        if (!$user->isAdmin()) {
            return false;
        }
        return $user->canAccessLocation($model->location);
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, User $model): bool
    {
        if (!$user->isAdmin()) {
            return false;
        }
        return $user->canAccessLocation($model->location);
    }

    public function delete(User $user, User $model): bool
    {
        return $user->hasRole(UserRole::OWNER);
    }

    public function restore(User $user, User $model): bool
    {
        return false;
    }

    public function forceDelete(User $user, User $model): bool
    {
        return false;
    }
}
