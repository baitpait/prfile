<?php

namespace App\Livewire\Concerns;

/**
 * Business Purpose: Single search field with apply button — draft until committed.
 */
trait UsesCommittedSearchFilter
{
    public string $searchDraft = '';

    public function mountUsesCommittedSearchFilter(): void
    {
        $this->searchDraft = $this->search;
    }

    public function applyListFilters(): void
    {
        $this->search = trim($this->searchDraft);
        $this->resetPage();
    }

    public function clearListFilters(): void
    {
        $this->search = '';
        $this->searchDraft = '';
        $this->resetPage();
    }

    public function hasActiveListFilters(): bool
    {
        return trim($this->search) !== '';
    }
}
