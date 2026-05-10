<?php

namespace App\Policies;

use App\Models\ClientPayment;
use App\Models\User;

class ClientPaymentPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, ClientPayment $payment): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->isAccountant();
    }

    public function update(User $user, ClientPayment $payment): bool
    {
        return $user->isAccountant();
    }

    public function delete(User $user, ClientPayment $payment): bool
    {
        return $user->isManager();
    }
}
