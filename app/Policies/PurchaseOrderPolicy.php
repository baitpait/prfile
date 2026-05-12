<?php

namespace App\Policies;

use App\Models\PurchaseOrder;
use App\Models\User;

class PurchaseOrderPolicy
{
    public function viewAny(User $user): bool
    {
        return (bool) $user->is_active;
    }

    public function view(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return (bool) $user->is_active;
    }

    public function create(User $user): bool
    {
        return $user->isAccountant();
    }

    public function update(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return $user->isAccountant();
    }

    public function delete(User $user, PurchaseOrder $purchaseOrder): bool
    {
        return $user->isManager();
    }
}
