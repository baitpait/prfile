<?php

namespace App\Policies;

use App\Models\SalaryAdvance;
use App\Models\User;

class SalaryAdvancePolicy
{
    public function viewAny(User $user): bool
    {
        return (bool) $user->is_active;
    }

    public function view(User $user, SalaryAdvance $salaryAdvance): bool
    {
        return (bool) $user->is_active;
    }

    public function create(User $user): bool
    {
        return $user->isAccountant();
    }

    public function update(User $user, SalaryAdvance $salaryAdvance): bool
    {
        return $user->isAccountant() && $salaryAdvance->status !== SalaryAdvance::STATUS_SETTLED;
    }

    public function delete(User $user, SalaryAdvance $salaryAdvance): bool
    {
        return $user->isAccountant() && $salaryAdvance->status !== SalaryAdvance::STATUS_SETTLED;
    }
}
