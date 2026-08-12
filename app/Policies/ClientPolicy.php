<?php

namespace App\Policies;

use App\Models\Client;
use App\Models\User;

class ClientPolicy
{
    public function viewAny(User $user): bool
    {
        return (bool) $user->is_active;
    }

    public function view(User $user, Client $client): bool
    {
        return (bool) $user->is_active;
    }

    public function create(User $user): bool
    {
        return $user->is_active && $user->isAccountant();
    }

    public function update(User $user, Client $client): bool
    {
        return $user->is_active && $user->isAccountant();
    }

    public function delete(User $user, Client $client): bool
    {
        return $user->is_active && $user->isManager();
    }

    public function viewStatement(User $user, Client $client): bool
    {
        return (bool) $user->is_active;
    }

    /**
     * تصدير CSV أو PDF لكشف العميل — للمحاسب والمدير فقط.
     */
    public function exportStatement(User $user, Client $client): bool
    {
        return $user->is_active && $user->isAccountant();
    }
}
