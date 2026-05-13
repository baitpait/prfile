<?php

namespace App\Policies;

use App\Models\LegacyCatalogProduct;
use App\Models\User;

class LegacyCatalogProductPolicy
{
    public function viewAny(User $user): bool
    {
        return (bool) $user->is_active;
    }

    public function view(User $user, LegacyCatalogProduct $legacyCatalogProduct): bool
    {
        return (bool) $user->is_active;
    }
}
