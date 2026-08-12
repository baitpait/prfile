<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_active && $user->isManager();
    }

    public function view(User $user, User $model): bool
    {
        return $user->is_active && $user->isManager();
    }

    public function create(User $user): bool
    {
        return $user->is_active && $user->isManager();
    }

    public function update(User $user, User $model): bool
    {
        return $user->is_active && $user->isManager();
    }

    public function delete(User $user, User $model): bool
    {
        return $user->is_active && $user->isManager() && $user->id !== $model->id;
    }

    /**
     * Business Purpose: Allow managers to enable/disable accounts except their own.
     */
    public function toggleActive(User $user, User $model): bool
    {
        return $user->is_active && $user->isManager() && $user->id !== $model->id;
    }
}
