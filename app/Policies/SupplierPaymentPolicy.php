<?php

namespace App\Policies;

use App\Models\SupplierPayment;
use App\Models\User;

class SupplierPaymentPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, SupplierPayment $payment): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->isAccountant();
    }

    public function update(User $user, SupplierPayment $payment): bool
    {
        return $user->isAccountant();
    }

    public function delete(User $user, SupplierPayment $payment): bool
    {
        return $user->isManager();
    }
}
