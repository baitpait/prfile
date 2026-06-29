<?php

namespace App\Livewire;

use App\Models\Employee;
use Livewire\Component;

class EmployeeShow extends Component
{
    public Employee $employee;

    public function mount(Employee $employee): void
    {
        $this->authorize('view', $employee);
        $this->employee = $employee->load(['salaryPayments' => fn ($q) => $q->orderByDesc('period_year')->orderByDesc('period_month')->limit(24)]);
    }

    public function render()
    {
        return view('livewire.employee-show');
    }
}
