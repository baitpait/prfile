<?php

namespace App\Livewire\Concerns;

/**
 * Business Purpose: Keep typed filter values in draft fields until the user clicks apply.
 */
trait UsesCommittedPartyDirectoryFilters
{
    public string $searchDraft = '';

    public string $filterCityDraft = '';

    /** @var 'newest'|'name' */
    public string $sortDraft = 'newest';

    public function mountUsesCommittedPartyDirectoryFilters(): void
    {
        $this->syncPartyFilterDraftsFromApplied();
    }

    protected function syncPartyFilterDraftsFromApplied(): void
    {
        $this->searchDraft = $this->search;
        $this->filterCityDraft = $this->filterCity;
        $this->sortDraft = $this->sort;
    }

    public function applyPartyFilters(): void
    {
        $this->search = trim($this->searchDraft);
        $this->filterCity = $this->filterCityDraft;
        $this->sort = in_array($this->sortDraft, ['newest', 'name'], true) ? $this->sortDraft : 'newest';
        $this->resetPage();
    }

    public function clearPartyFilters(): void
    {
        $this->search = '';
        $this->filterCity = '';
        $this->sort = 'newest';
        $this->syncPartyFilterDraftsFromApplied();
        $this->resetPage();
    }
}
