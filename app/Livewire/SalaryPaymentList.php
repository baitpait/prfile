<?php

namespace App\Livewire;

use App\Livewire\Concerns\UsesCommittedSearchFilter;
use App\Livewire\Concerns\WithPerPagePagination;
use App\Models\Employee;
use App\Models\SalaryPayment;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class SalaryPaymentList extends Component
{
    use UsesCommittedSearchFilter;
    use WithPagination;
    use WithPerPagePagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url]
    public string $status = '';

    #[Url]
    public string $period = '';

    #[Url(as: 'employee_id')]
    public string $employeeId = '';

    public ?int $confirmDeleteId = null;

    public function mount(): void
    {
        $this->authorize('viewAny', SalaryPayment::class);
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
        $this->period = '';
        $this->employeeId = '';
        $this->resetPage();
    }

    public function hasActiveListFilters(): bool
    {
        return trim($this->search) !== ''
            || $this->status !== ''
            || $this->period !== ''
            || $this->employeeId !== '';
    }

    public function confirmDelete(int $id): void
    {
        $this->authorize('delete', SalaryPayment::findOrFail($id));
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

        $payment = SalaryPayment::findOrFail($this->confirmDeleteId);
        $this->authorize('delete', $payment);
        $payment->delete();
        $this->confirmDeleteId = null;
        $this->dispatch('toast', message: 'تم حذف سجل الراتب');
    }

    public function render()
    {
        $query = SalaryPayment::query()
            ->with('employee')
            ->when($this->employeeId !== '', fn ($q) => $q->where('employee_id', (int) $this->employeeId))
            ->when($this->status !== '', fn ($q) => $q->where('status', $this->status))
            ->when($this->period !== '', function ($q) {
                [$year, $month] = array_pad(explode('-', $this->period, 2), 2, null);
                if ($year && $month) {
                    $q->where('period_year', (int) $year)->where('period_month', (int) $month);
                }
            })
            ->when(trim($this->search) !== '', function ($q) {
                $s = '%'.trim($this->search).'%';
                $q->whereHas('employee', fn ($eq) => $eq->where('full_name', 'like', $s)
                    ->orWhere('employee_code', 'like', $s));
            })
            ->orderByDesc('period_year')
            ->orderByDesc('period_month')
            ->orderBy('employee_id');

        return view('livewire.salary-payment-list', [
            'rows' => $this->paginateWithPerPage($query),
            'employees' => Employee::query()->where('is_active', true)->orderBy('full_name')->pluck('full_name', 'id'),
        ]);
    }
}
