<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;

/**
 * Business Purpose: Unified name/phone/email search for client and supplier directory lists.
 */
trait HasDirectorySearch
{
    /**
     * @param  Builder<static>  $query
     */
    public function scopeDirectorySearch(Builder $query, ?string $term): Builder
    {
        $term = trim((string) $term);
        if ($term === '') {
            return $query;
        }

        $like = '%'.$term.'%';

        return $query->where(function (Builder $q) use ($like): void {
            $q->where('business_name', 'like', $like)
                ->orWhere('first_name', 'like', $like)
                ->orWhere('last_name', 'like', $like)
                ->orWhere('email', 'like', $like)
                ->orWhere('phone_primary', 'like', $like)
                ->orWhere('phone_secondary', 'like', $like)
                ->orWhere('city', 'like', $like);
        });
    }
}
