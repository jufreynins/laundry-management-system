<?php

namespace App\Policies;

use App\Models\Expense;
use App\Models\User;
use Illuminate\Auth\Access\Response;

use App\Enums\UserRole;

class ExpensePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(UserRole::OWNER, UserRole::MANAGER, UserRole::ACCOUNTANT);
    }

    public function view(User $user, Expense $expense): bool
    {
        return $this->viewAny($user) && $user->canAccessLocation($expense->location);
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Expense $expense): bool
    {
        return false;
    }

    public function delete(User $user, Expense $expense): bool
    {
        return false;
    }

    public function restore(User $user, Expense $expense): bool
    {
        return false;
    }

    public function forceDelete(User $user, Expense $expense): bool
    {
        return false;
    }
}
