<?php

namespace App\Livewire;

use App\Models\Expense;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class ExpenseList extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    public function updatedSearch(): void { $this->resetPage(); }

    public function deleteRecord(int $id): void
    {
        Expense::findOrFail($id)->delete();
        $this->dispatch('toast', message: 'تم حذف المصروف');
    }

    public function render()
    {
        $rows = Expense::query()
            ->when($this->search, function ($q) {
                $s = "%{$this->search}%";
                $q->where(fn($q) =>
                    $q->where('description', 'like', $s)
                      ->orWhere('notes',       'like', $s)
                );
            })
            ->latest('expense_date')->paginate(15);

        return view('livewire.expense-list', ['rows' => $rows]);
    }
}
