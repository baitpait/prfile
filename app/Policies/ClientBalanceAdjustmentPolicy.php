<?php

namespace App\Policies;

use App\Models\ClientBalanceAdjustment;
use App\Models\User;

class ClientBalanceAdjustmentPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, ClientBalanceAdjustment $adjustment): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->isAccountant();
    }

    public function update(User $user, ClientBalanceAdjustment $adjustment): bool
    {
        return $user->isAccountant();
    }

    public function delete(User $user, ClientBalanceAdjustment $adjustment): bool
    {
        return $user->isManager();
    }
}
