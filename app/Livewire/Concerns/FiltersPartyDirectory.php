<?php

namespace App\Livewire\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Livewire\Attributes\Url;

/**
 * Business Purpose: Search and filter party directory lists (clients, suppliers) with URL-synced state.
 */
trait FiltersPartyDirectory
{
    use UsesCommittedPartyDirectoryFilters;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'city')]
    public string $filterCity = '';

    /** @var 'newest'|'name' */
    #[Url(as: 'sort')]
    public string $sort = 'newest';

    public function hasActivePartyFilters(): bool
    {
        return trim($this->search) !== ''
            || $this->filterCity !== ''
            || $this->sort !== 'newest';
    }

    /**
     * @return class-string<Model>
     */
    abstract protected function partyModelClass(): string;

    /**
     * @return Builder<Model>
     */
    protected function partyDirectoryQuery(): Builder
    {
        $modelClass = $this->partyModelClass();

        $query = $modelClass::query()
            ->directorySearch($this->search);

        if ($this->filterCity !== '') {
            $query->where('city', $this->filterCity);
        }

        if ($this->sort === 'name') {
            $query->orderBy('business_name')
                ->orderBy('first_name')
                ->orderBy('last_name')
                ->orderBy('id');
        } else {
            $query->latest();
        }

        return $query;
    }

    /**
     * @return list<string>
     */
    protected function partyFilterCities(): array
    {
        $modelClass = $this->partyModelClass();

        return $modelClass::query()
            ->whereNotNull('city')
            ->where('city', '!=', '')
            ->distinct()
            ->orderBy('city')
            ->pluck('city')
            ->all();
    }
}
