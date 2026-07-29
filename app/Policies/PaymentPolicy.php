<?php

namespace App\Policies;

use App\Models\Payment;
use App\Models\User;
use Illuminate\Auth\Access\Response;

use App\Enums\UserRole;

class PaymentPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Payment $payment): bool
    {
        return $user->canAccessLocation($payment->location);
    }

    public function create(User $user): bool
    {
        return !$user->hasRole(UserRole::ACCOUNTANT);
    }

    public function update(User $user, Payment $payment): bool
    {
        return false;
    }

    public function void(User $user, Payment $payment): bool
    {
        return $user->canAccessLocation($payment->location) && $user->hasRole(UserRole::OWNER, UserRole::MANAGER);
    }

    public function refund(User $user, Payment $payment): bool
    {
        return $user->canAccessLocation($payment->location) && $user->hasRole(UserRole::OWNER, UserRole::MANAGER);
    }

    public function delete(User $user, Payment $payment): bool
    {
        return false;
    }

    public function restore(User $user, Payment $payment): bool
    {
        return false;
    }

    public function forceDelete(User $user, Payment $payment): bool
    {
        return false;
    }
}
