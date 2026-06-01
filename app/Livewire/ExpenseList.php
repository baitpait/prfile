<?php

namespace App\Livewire;

use App\Livewire\Concerns\FiltersCashflowList;
use App\Livewire\Concerns\UsesCommittedSearchFilter;
use App\Livewire\Concerns\WithPerPagePagination;
use App\Models\Expense;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class ExpenseList extends Component
{
    use FiltersCashflowList;
    use UsesCommittedSearchFilter;
    use WithPagination;
    use WithPerPagePagination;

    #[Url(as: 'q')]
    public string $search = '';

    public function applyListFilters(): void
    {
        $this->search = trim($this->searchDraft);
        $this->commitCashflowFilterDrafts();
        $this->resetPage();
    }

    public function clearListFilters(): void
    {
        $this->search = '';
        $this->searchDraft = '';
        $this->resetCashflowFilters();
        $this->resetPage();
    }

    public function hasActiveListFilters(): bool
    {
        return trim($this->search) !== ''
            || $this->hasActiveCashflowFilters();
    }

    public function deleteRecord(int $id): void
    {
        Expense::findOrFail($id)->delete();
        $this->dispatch('toast', message: 'تم حذف المصروف');
    }

    public function render()
    {
        $currencies = Expense::query()
            ->whereNotNull('currency_code')
            ->where('currency_code', '!=', '')
            ->distinct()
            ->orderBy('currency_code')
            ->pluck('currency_code')
            ->values()
            ->all();

        $query = Expense::query()
            ->when($this->search, function ($q) {
                $s = '%'.$this->search.'%';
                $q->where(fn ($q) => $q->where('description', 'like', $s)
                    ->orWhere('notes', 'like', $s)
                );
            });

        $this->applyCashflowCurrencyFilter($query, $currencies);
        $this->applyCashflowDateFilters($query, 'expense_date');

        $rows = $this->paginateWithPerPage($query->latest('expense_date'));

        return view('livewire.expense-list', [
            'rows' => $rows,
            'currencies' => $currencies,
        ]);
    }
}
