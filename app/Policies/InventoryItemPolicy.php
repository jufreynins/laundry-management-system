<?php

namespace App\Policies;

use App\Models\InventoryItem;
use App\Models\User;
use Illuminate\Auth\Access\Response;

use App\Enums\UserRole;

class InventoryItemPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(UserRole::OWNER, UserRole::MANAGER, UserRole::ACCOUNTANT, UserRole::LAUNDRY_STAFF);
    }

    public function view(User $user, InventoryItem $inventoryItem): bool
    {
        return $this->viewAny($user) && $user->canAccessLocation($inventoryItem->location);
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, InventoryItem $inventoryItem): bool
    {
        return $user->isAdmin() && $user->canAccessLocation($inventoryItem->location);
    }

    public function recordTransaction(User $user, InventoryItem $inventoryItem): bool
    {
        return $user->hasRole(UserRole::OWNER, UserRole::MANAGER, UserRole::LAUNDRY_STAFF)
            && $user->canAccessLocation($inventoryItem->location);
    }

    public function delete(User $user, InventoryItem $inventoryItem): bool
    {
        return false;
    }

    public function restore(User $user, InventoryItem $inventoryItem): bool
    {
        return false;
    }

    public function forceDelete(User $user, InventoryItem $inventoryItem): bool
    {
        return false;
    }
}
