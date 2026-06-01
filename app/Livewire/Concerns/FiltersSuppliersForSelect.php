<?php

namespace App\Livewire\Concerns;

use App\Models\Supplier;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Business Purpose: Filter supplier dropdown by typed name, phone, or email on Livewire forms and lists.
 */
trait FiltersSuppliersForSelect
{
    public string $supplierSearch = '';

    /**
     * @return Collection<int, Supplier>
     */
    protected function suppliersForSelect(): Collection
    {
        $query = Supplier::query()
            ->whereNull('deleted_at')
            ->orderBy('business_name')
            ->orderBy('first_name')
            ->orderBy('id');

        $term = trim($this->supplierSearch);
        if ($term !== '') {
            $like = '%'.$term.'%';
            $query->where(function ($q) use ($like): void {
                $q->where('business_name', 'like', $like)
                    ->orWhere('first_name', 'like', $like)
                    ->orWhere('last_name', 'like', $like)
                    ->orWhere('phone_primary', 'like', $like)
                    ->orWhere('phone_secondary', 'like', $like)
                    ->orWhere('email', 'like', $like);
            });
        }

        $suppliers = $query->limit(80)->get();

        $selectedId = (string) ($this->filterSupplierId ?? '');
        if ($selectedId !== '' && ctype_digit($selectedId)) {
            $id = (int) $selectedId;
            if ($id > 0 && ! $suppliers->contains('id', $id)) {
                $selected = Supplier::query()->whereKey($id)->whereNull('deleted_at')->first();
                if ($selected !== null) {
                    $suppliers->prepend($selected);
                }
            }
        }

        return $suppliers;
    }

    /**
     * @param  Builder<\Illuminate\Database\Eloquent\Model>  $query
     */
    protected function applySupplierSearchToPartyRelation(Builder $query, string $relation = 'supplier'): void
    {
        if (property_exists($this, 'filterSupplierId') && (string) $this->filterSupplierId !== '') {
            return;
        }

        $term = trim($this->supplierSearch);
        if ($term === '') {
            return;
        }

        $like = '%'.$term.'%';
        $query->whereHas($relation, function ($q) use ($like): void {
            $q->where('business_name', 'like', $like)
                ->orWhere('first_name', 'like', $like)
                ->orWhere('last_name', 'like', $like)
                ->orWhere('phone_primary', 'like', $like)
                ->orWhere('phone_secondary', 'like', $like)
                ->orWhere('email', 'like', $like);
        });
    }
}
