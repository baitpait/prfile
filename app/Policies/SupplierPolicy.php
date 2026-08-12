<?php

namespace App\Policies;

use App\Models\Supplier;
use App\Models\User;

class SupplierPolicy
{
    public function viewAny(User $user): bool
    {
        return (bool) $user->is_active;
    }

    public function view(User $user, Supplier $supplier): bool
    {
        return (bool) $user->is_active;
    }

    public function create(User $user): bool
    {
        return $user->is_active && $user->isAccountant();
    }

    public function update(User $user, Supplier $supplier): bool
    {
        return $user->is_active && $user->isAccountant();
    }

    public function delete(User $user, Supplier $supplier): bool
    {
        return $user->is_active && $user->isManager();
    }

    public function viewStatement(User $user, Supplier $supplier): bool
    {
        return (bool) $user->is_active;
    }

    /**
     * تصدير CSV أو PDF لكشف المورد — للمحاسب والمدير فقط.
     */
    public function exportStatement(User $user, Supplier $supplier): bool
    {
        return $user->is_active && $user->isAccountant();
    }
}
