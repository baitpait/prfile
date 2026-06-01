<?php

namespace App\Livewire\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Livewire\Attributes\Url;

/**
 * Business Purpose: Paginated party directory lists with live name search.
 */
trait ListsPartyDirectory
{
    #[Url(as: 'q')]
    public string $search = '';

    /**
     * @return class-string<Model>
     */
    abstract protected function partyModelClass(): string;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    /**
     * @return Builder<Model>
     */
    protected function partyDirectoryQuery(): Builder
    {
        return $this->partyModelClass()::query()
            ->partyNameSearch($this->search)
            ->latest();
    }
}
