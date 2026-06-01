<?php

namespace App\Livewire;

use App\Livewire\Concerns\AppliesListFiltersOnAction;
use App\Livewire\Concerns\WithPerPagePagination;
use App\Models\IncomeEntry;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class IncomeEntryList extends Component
{
    use AppliesListFiltersOnAction;
    use WithPagination;
    use WithPerPagePagination;

    #[Url(as: 'q')]
    public string $search = '';

    public function clearListFilters(): void
    {
        $this->search = '';
        $this->resetPage();
    }

    public function hasActiveListFilters(): bool
    {
        return trim($this->search) !== '';
    }

    public function deleteRecord(int $id): void
    {
        IncomeEntry::findOrFail($id)->delete();
        $this->dispatch('toast', message: 'تم حذف الإيراد');
    }

    public function render()
    {
        $rows = $this->paginateWithPerPage(
            IncomeEntry::query()
                ->when($this->search, function ($q) {
                    $s = "%{$this->search}%";
                    $q->where(fn ($q) => $q->where('description', 'like', $s)
                        ->orWhere('notes', 'like', $s)
                    );
                })
                ->latest('income_date')
        );

        return view('livewire.income-entry-list', ['rows' => $rows]);
    }
}
