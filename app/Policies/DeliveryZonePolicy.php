<?php

namespace App\Policies;

use App\Models\DeliveryZone;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class DeliveryZonePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, DeliveryZone $deliveryZone): bool
    {
        return $user->canAccessLocation($deliveryZone->location);
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, DeliveryZone $deliveryZone): bool
    {
        return $user->canAccessLocation($deliveryZone->location) && $user->isAdmin();
    }

    public function delete(User $user, DeliveryZone $deliveryZone): bool
    {
        return false;
    }

    public function restore(User $user, DeliveryZone $deliveryZone): bool
    {
        return false;
    }

    public function forceDelete(User $user, DeliveryZone $deliveryZone): bool
    {
        return false;
    }
}
