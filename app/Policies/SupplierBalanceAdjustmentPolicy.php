<?php

namespace App\Policies;

use App\Models\SupplierBalanceAdjustment;
use App\Models\User;

class SupplierBalanceAdjustmentPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, SupplierBalanceAdjustment $adjustment): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->isAccountant();
    }

    public function update(User $user, SupplierBalanceAdjustment $adjustment): bool
    {
        return $user->isAccountant();
    }

    public function delete(User $user, SupplierBalanceAdjustment $adjustment): bool
    {
        return $user->isManager();
    }
}
