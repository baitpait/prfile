<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;

class ProductPolicy
{
    public function viewAny(User $user): bool
    {
        return (bool) $user->is_active;
    }

    public function view(User $user, Product $product): bool
    {
        return (bool) $user->is_active;
    }

    public function create(User $user): bool
    {
        return $user->is_active && $user->isAccountant();
    }

    public function update(User $user, Product $product): bool
    {
        return $user->is_active && $user->isAccountant();
    }

    public function delete(User $user, Product $product): bool
    {
        return $user->is_active && $user->isManager();
    }
}
