<?php

namespace App\Policies;

use App\Models\Supplier;
use App\Models\User;

class SupplierPolicy
{
    public function viewStatement(User $user, Supplier $supplier): bool
    {
        return $user->is_active;
    }

    /**
     * تصدير CSV أو PDF لكشف المورد — للمحاسب والمدير فقط.
     */
    public function exportStatement(User $user, Supplier $supplier): bool
    {
        return $user->is_active && $user->isAccountant();
    }
}
