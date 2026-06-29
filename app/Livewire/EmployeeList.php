<?php

namespace App\Livewire;

use App\Livewire\Concerns\UsesCommittedSearchFilter;
use App\Livewire\Concerns\WithPerPagePagination;
use App\Models\Employee;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class EmployeeList extends Component
{
    use UsesCommittedSearchFilter;
    use WithPagination;
    use WithPerPagePagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url]
    public string $active = '';

    public ?int $confirmDeleteId = null;

    public function mount(): void
    {
        $this->authorize('viewAny', Employee::class);
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
        $this->active = '';
        $this->resetPage();
    }

    public function hasActiveListFilters(): bool
    {
        return trim($this->search) !== '' || $this->active !== '';
    }

    public function confirmDelete(int $id): void
    {
        $this->authorize('delete', Employee::findOrFail($id));
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

        $employee = Employee::findOrFail($this->confirmDeleteId);
        $this->authorize('delete', $employee);
        $employee->delete();
        $this->confirmDeleteId = null;
        $this->dispatch('toast', message: 'تم حذف الموظف');
    }

    public function render()
    {
        $query = Employee::query()
            ->when(trim($this->search) !== '', function ($q) {
                $s = '%'.trim($this->search).'%';
                $q->where(function ($q) use ($s) {
                    $q->where('full_name', 'like', $s)
                        ->orWhere('employee_code', 'like', $s)
                        ->orWhere('phone_primary', 'like', $s)
                        ->orWhere('department', 'like', $s);
                });
            })
            ->when($this->active === '1', fn ($q) => $q->where('is_active', true))
            ->when($this->active === '0', fn ($q) => $q->where('is_active', false))
            ->orderBy('full_name');

        return view('livewire.employee-list', [
            'rows' => $this->paginateWithPerPage($query),
        ]);
    }
}
