<?php

namespace App\Policies;

use App\Models\SalaryPayment;
use App\Models\User;

class SalaryPaymentPolicy
{
    public function viewAny(User $user): bool
    {
        return (bool) $user->is_active;
    }

    public function view(User $user, SalaryPayment $salaryPayment): bool
    {
        return (bool) $user->is_active;
    }

    public function create(User $user): bool
    {
        return $user->isAccountant();
    }

    public function update(User $user, SalaryPayment $salaryPayment): bool
    {
        return $user->isAccountant();
    }

    public function delete(User $user, SalaryPayment $salaryPayment): bool
    {
        return $user->isAccountant();
    }
}
