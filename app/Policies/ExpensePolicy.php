<?php

namespace App\Policies;

use App\Models\Expense;
use App\Models\User;

class ExpensePolicy
{
    public function viewAny(User $user): bool
    {
        return (bool) $user->is_active;
    }

    public function view(User $user, Expense $expense): bool
    {
        return (bool) $user->is_active;
    }

    public function create(User $user): bool
    {
        return $user->is_active && $user->isAccountant();
    }

    public function update(User $user, Expense $expense): bool
    {
        return $user->is_active && $user->isAccountant();
    }

    public function delete(User $user, Expense $expense): bool
    {
        return $user->is_active && $user->isManager();
    }
}
