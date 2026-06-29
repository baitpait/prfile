<?php

namespace App\Policies;

use App\Models\Employee;
use App\Models\User;

class EmployeePolicy
{
    public function viewAny(User $user): bool
    {
        return (bool) $user->is_active;
    }

    public function view(User $user, Employee $employee): bool
    {
        return (bool) $user->is_active;
    }

    public function create(User $user): bool
    {
        return $user->isManager();
    }

    public function update(User $user, Employee $employee): bool
    {
        return $user->isManager();
    }

    public function delete(User $user, Employee $employee): bool
    {
        return $user->isManager();
    }
}
