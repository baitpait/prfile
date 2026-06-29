<?php

namespace App\Livewire;

use App\Models\Employee;
use App\Models\Product;
use Livewire\Component;

class EmployeeForm extends Component
{
    public ?int $employeeId = null;

    public string $employee_code = '';

    public string $full_name = '';

    public string $phone_primary = '';

    public string $phone_secondary = '';

    public string $email = '';

    public string $national_id = '';

    public string $job_title = '';

    public string $department = '';

    public string $hire_date = '';

    public string $termination_date = '';

    public string $base_salary_amount = '0';

    public string $base_salary_currency = 'ILS';

    public string $pay_frequency = Employee::PAY_FREQUENCY_MONTHLY;

    public string $bank_name = '';

    public string $bank_account = '';

    public string $notes = '';

    public bool $is_active = true;

    /** @var list<string> */
    public array $currencyOptions = [];

    public function mount(?Employee $employee = null): void
    {
        $this->currencyOptions = Product::billingCurrencies();

        if ($employee && $employee->exists) {
            $this->authorize('update', $employee);
            $this->employeeId = $employee->id;
            $this->employee_code = $employee->employee_code ?? '';
            $this->full_name = $employee->full_name;
            $this->phone_primary = $employee->phone_primary ?? '';
            $this->phone_secondary = $employee->phone_secondary ?? '';
            $this->email = $employee->email ?? '';
            $this->national_id = $employee->national_id ?? '';
            $this->job_title = $employee->job_title ?? '';
            $this->department = $employee->department ?? '';
            $this->hire_date = $employee->hire_date?->format('Y-m-d') ?? '';
            $this->termination_date = $employee->termination_date?->format('Y-m-d') ?? '';
            $this->base_salary_amount = (string) $employee->base_salary_amount;
            $this->base_salary_currency = $employee->base_salary_currency ?? 'ILS';
            $this->pay_frequency = $employee->pay_frequency ?? Employee::PAY_FREQUENCY_MONTHLY;
            $this->bank_name = $employee->bank_name ?? '';
            $this->bank_account = $employee->bank_account ?? '';
            $this->notes = $employee->notes ?? '';
            $this->is_active = (bool) $employee->is_active;
        } else {
            $this->authorize('create', Employee::class);
        }
    }

    public function save(): void
    {
        $this->validate([
            'full_name' => 'required|string|max:120',
            'employee_code' => 'nullable|string|max:32|unique:employees,employee_code'.($this->employeeId ? ",{$this->employeeId}" : ''),
            'email' => 'nullable|email|max:120',
            'base_salary_amount' => 'required|numeric|min:0',
            'base_salary_currency' => 'required|string|size:3|in:'.implode(',', $this->currencyOptions),
            'pay_frequency' => 'required|in:'.implode(',', array_keys(Employee::employmentTypeOptions())),
            'hire_date' => 'nullable|date',
            'termination_date' => 'nullable|date|after_or_equal:hire_date',
        ], [], [
            'full_name' => 'الاسم الكامل',
            'employee_code' => 'الرقم الوظيفي',
            'base_salary_amount' => 'الراتب الأساسي',
            'base_salary_currency' => 'عملة الراتب',
            'pay_frequency' => 'نوع التوظيف',
        ]);

        $data = [
            'employee_code' => $this->employee_code !== '' ? $this->employee_code : null,
            'full_name' => $this->full_name,
            'phone_primary' => $this->phone_primary !== '' ? $this->phone_primary : null,
            'phone_secondary' => $this->phone_secondary !== '' ? $this->phone_secondary : null,
            'email' => $this->email !== '' ? $this->email : null,
            'national_id' => $this->national_id !== '' ? $this->national_id : null,
            'job_title' => $this->job_title !== '' ? $this->job_title : null,
            'department' => $this->department !== '' ? $this->department : null,
            'hire_date' => $this->hire_date !== '' ? $this->hire_date : null,
            'termination_date' => $this->termination_date !== '' ? $this->termination_date : null,
            'base_salary_amount' => $this->base_salary_amount,
            'base_salary_currency' => $this->base_salary_currency,
            'pay_frequency' => $this->pay_frequency,
            'bank_name' => $this->bank_name !== '' ? $this->bank_name : null,
            'bank_account' => $this->bank_account !== '' ? $this->bank_account : null,
            'notes' => $this->notes !== '' ? $this->notes : null,
            'is_active' => $this->is_active,
        ];

        if ($this->employeeId) {
            $employee = Employee::findOrFail($this->employeeId);
            $this->authorize('update', $employee);
            $employee->update($data);
            $msg = 'تم تحديث بيانات الموظف';
        } else {
            $data['recorded_by_user_id'] = auth()->id();
            Employee::create($data);
            $msg = 'تم إضافة الموظف بنجاح';
        }

        session()->flash('toast', $msg);
        $this->redirect(route('employees.index'), navigate: true);
    }

    public function render()
    {
        return view('livewire.employee-form', [
            'employmentTypeOptions' => Employee::employmentTypeOptions(),
        ]);
    }
}
