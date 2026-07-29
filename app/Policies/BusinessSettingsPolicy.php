<?php

namespace App\Policies;

use App\Models\BusinessSettings;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class BusinessSettingsPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, BusinessSettings $setting): bool
    {
        return $user->canAccessLocation($setting->location) && $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, BusinessSettings $setting): bool
    {
        return $user->canAccessLocation($setting->location) && $user->isAdmin();
    }

    public function delete(User $user, BusinessSettings $setting): bool
    {
        return false;
    }

    public function restore(User $user, BusinessSettings $setting): bool
    {
        return false;
    }

    public function forceDelete(User $user, BusinessSettings $setting): bool
    {
        return false;
    }
}
