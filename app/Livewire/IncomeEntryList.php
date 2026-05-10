<?php

namespace App\Livewire;

use App\Models\IncomeEntry;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class IncomeEntryList extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    public ?int $confirmDeleteId = null;

    public function updatedSearch(): void { $this->resetPage(); }

    public function confirmDelete(int $id): void
    {
        $this->confirmDeleteId = $id;
    }

    public function cancelDelete(): void { $this->confirmDeleteId = null; }

    public function delete(): void
    {
        if ($this->confirmDeleteId) {
            IncomeEntry::findOrFail($this->confirmDeleteId)->delete();
            $this->confirmDeleteId = null;
            $this->dispatch('toast', message: 'تم حذف الإيراد');
        }
    }

    public function render()
    {
        $rows = IncomeEntry::query()
            ->when($this->search, function ($q) {
                $s = "%{$this->search}%";
                $q->where(fn($q) =>
                    $q->where('description', 'like', $s)
                      ->orWhere('notes',       'like', $s)
                );
            })
            ->latest('income_date')->paginate(15);

        return view('livewire.income-entry-list', ['rows' => $rows]);
    }
}
