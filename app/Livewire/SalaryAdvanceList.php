<?php

namespace App\Livewire;

use App\Livewire\Concerns\UsesCommittedSearchFilter;
use App\Livewire\Concerns\WithPerPagePagination;
use App\Models\Employee;
use App\Models\SalaryAdvance;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class SalaryAdvanceList extends Component
{
    use UsesCommittedSearchFilter;
    use WithPagination;
    use WithPerPagePagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url]
    public string $status = '';

    #[Url(as: 'employee_id')]
    public string $employeeId = '';

    #[Url]
    public string $dateFrom = '';

    #[Url]
    public string $dateTo = '';

    public ?int $confirmDeleteId = null;

    public function mount(): void
    {
        $this->authorize('viewAny', SalaryAdvance::class);
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
        $this->status = '';
        $this->employeeId = '';
        $this->dateFrom = '';
        $this->dateTo = '';
        $this->resetPage();
    }

    public function hasActiveListFilters(): bool
    {
        return trim($this->search) !== ''
            || $this->status !== ''
            || $this->employeeId !== ''
            || $this->dateFrom !== ''
            || $this->dateTo !== '';
    }

    public function confirmDelete(int $id): void
    {
        $this->authorize('delete', SalaryAdvance::findOrFail($id));
        $this->confirmDeleteId = $id;
    }

    public function cancelDelete(): void
    {
        $this->confirmDeleteId = null;
    }

    public function delete(): void
    {
        if ($this->confirmDeleteId === null) {
            return;
        }

        $advance = SalaryAdvance::findOrFail($this->confirmDeleteId);
        $this->authorize('delete', $advance);
        $advance->delete();
        $this->confirmDeleteId = null;
        $this->dispatch('toast', message: 'تم حذف السلفة');
    }

    public function render()
    {
        $query = SalaryAdvance::query()
            ->with('employee')
            ->when($this->employeeId !== '', fn ($q) => $q->where('employee_id', (int) $this->employeeId))
            ->when($this->status !== '', fn ($q) => $q->where('status', $this->status))
            ->when($this->dateFrom !== '', fn ($q) => $q->whereDate('paid_at', '>=', $this->dateFrom))
            ->when($this->dateTo !== '', fn ($q) => $q->whereDate('paid_at', '<=', $this->dateTo))
            ->when(trim($this->search) !== '', function ($q) {
                $s = '%'.trim($this->search).'%';
                $q->whereHas('employee', fn ($eq) => $eq->where('full_name', 'like', $s)
                    ->orWhere('employee_code', 'like', $s));
            })
            ->orderByDesc('paid_at')
            ->orderByDesc('id');

        return view('livewire.salary-advance-list', [
            'rows' => $this->paginateWithPerPage($query),
            'employees' => Employee::query()->where('is_active', true)->orderBy('full_name')->pluck('full_name', 'id'),
        ]);
    }
}
