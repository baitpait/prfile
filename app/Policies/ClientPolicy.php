<?php

namespace App\Policies;

use App\Models\Client;
use App\Models\User;

class ClientPolicy
{
    public function viewStatement(User $user, Client $client): bool
    {
        return $user->is_active;
    }

    /**
     * تصدير CSV أو PDF لكشف العميل — للمحاسب والمدير فقط.
     */
    public function exportStatement(User $user, Client $client): bool
    {
        return $user->is_active && $user->isAccountant();
    }
}
