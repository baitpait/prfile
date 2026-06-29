<?php

namespace App\Livewire;

use App\Models\Employee;
use App\Models\Product;
use App\Models\SalaryPayment;
use Livewire\Component;

class SalaryPaymentForm extends Component
{
    public ?int $recordId = null;

    public string $employee_id = '';

    public string $period_year = '';

    public string $period_month = '';

    public string $base_amount = '0';

    public string $bonus_amount = '0';

    public string $deduction_amount = '0';

    public string $currency_code = 'ILS';

    public string $paid_at = '';

    public string $method = 'bank';

    public string $bank_reference = '';

    public string $status = SalaryPayment::STATUS_DRAFT;

    public string $notes = '';

    /** @var array<int, string> */
    public array $employeeOptions = [];

    /** @var list<string> */
    public array $currencyOptions = [];

    public function mount(?SalaryPayment $salaryPayment = null): void
    {
        $this->currencyOptions = Product::billingCurrencies();
        $this->employeeOptions = Employee::query()
            ->where('is_active', true)
            ->orderBy('full_name')
            ->pluck('full_name', 'id')
            ->all();

        if ($salaryPayment && $salaryPayment->exists) {
            $this->authorize('update', $salaryPayment);
            $this->recordId = $salaryPayment->id;
            $this->employee_id = (string) $salaryPayment->employee_id;
            $this->period_year = (string) $salaryPayment->period_year;
            $this->period_month = (string) $salaryPayment->period_month;
            $this->base_amount = (string) $salaryPayment->base_amount;
            $this->bonus_amount = (string) $salaryPayment->bonus_amount;
            $this->deduction_amount = (string) $salaryPayment->deduction_amount;
            $this->currency_code = $salaryPayment->currency_code;
            $this->paid_at = $salaryPayment->paid_at?->format('Y-m-d') ?? '';
            $this->method = $salaryPayment->method ?? 'bank';
            $this->bank_reference = $salaryPayment->bank_reference ?? '';
            $this->status = $salaryPayment->status;
            $this->notes = $salaryPayment->notes ?? '';
        } else {
            $this->authorize('create', SalaryPayment::class);
            $this->period_year = (string) now()->year;
            $this->period_month = (string) now()->month;
            $this->paid_at = now()->format('Y-m-d');

            $prefillEmployeeId = request()->query('employee_id');
            if ($prefillEmployeeId !== null && $prefillEmployeeId !== '') {
                $this->employee_id = (string) $prefillEmployeeId;
                $this->updatedEmployeeId();
            }
        }
    }

    public function updatedEmployeeId(): void
    {
        if ($this->employee_id === '' || $this->recordId) {
            return;
        }

        $employee = Employee::find((int) $this->employee_id);
        if ($employee === null) {
            return;
        }

        $this->base_amount = (string) $employee->base_salary_amount;
        $this->currency_code = $employee->base_salary_currency ?? 'ILS';
    }

    public function netAmount(): float
    {
        return SalaryPayment::computeNet(
            (float) $this->base_amount,
            (float) $this->bonus_amount,
            (float) $this->deduction_amount,
        );
    }

    public function save(): void
    {
        $employeeId = (int) $this->employee_id;
        $year = (int) $this->period_year;
        $month = (int) $this->period_month;

        $this->validate([
            'employee_id' => 'required|exists:employees,id',
            'period_year' => 'required|integer|min:2000|max:2100',
            'period_month' => 'required|integer|min:1|max:12',
            'base_amount' => 'required|numeric|min:0',
            'bonus_amount' => 'nullable|numeric|min:0',
            'deduction_amount' => 'nullable|numeric|min:0',
            'currency_code' => 'required|string|size:3|in:'.implode(',', $this->currencyOptions),
            'status' => 'required|in:draft,paid,cancelled',
            'paid_at' => $this->status === SalaryPayment::STATUS_PAID ? 'required|date' : 'nullable|date',
            'method' => $this->status === SalaryPayment::STATUS_PAID ? 'required|in:cash,bank,check,transfer' : 'nullable|in:cash,bank,check,transfer',
        ], [], [
            'employee_id' => 'الموظف',
            'period_year' => 'السنة',
            'period_month' => 'الشهر',
            'base_amount' => 'الراتب الأساسي',
        ]);

        $uniqueRule = \Illuminate\Validation\Rule::unique('salary_payments', 'employee_id')
            ->where('period_year', $year)
            ->where('period_month', $month)
            ->where('currency_code', $this->currency_code)
            ->whereNull('deleted_at');

        if ($this->recordId) {
            $uniqueRule->ignore($this->recordId);
        }

        $this->validate(['employee_id' => $uniqueRule], [
            'employee_id.unique' => 'يوجد سجل راتب لهذا الموظف في نفس الشهر والعملة.',
        ]);

        $net = $this->netAmount();

        $data = [
            'employee_id' => $employeeId,
            'period_year' => $year,
            'period_month' => $month,
            'base_amount' => $this->base_amount,
            'bonus_amount' => $this->bonus_amount !== '' ? $this->bonus_amount : 0,
            'deduction_amount' => $this->deduction_amount !== '' ? $this->deduction_amount : 0,
            'net_amount' => $net,
            'currency_code' => $this->currency_code,
            'paid_at' => $this->status === SalaryPayment::STATUS_PAID ? ($this->paid_at ?: now()->format('Y-m-d')) : null,
            'method' => $this->status === SalaryPayment::STATUS_PAID ? $this->method : null,
            'bank_reference' => $this->bank_reference !== '' ? $this->bank_reference : null,
            'status' => $this->status,
            'notes' => $this->notes !== '' ? $this->notes : null,
        ];

        if ($this->recordId) {
            $payment = SalaryPayment::findOrFail($this->recordId);
            $this->authorize('update', $payment);
            $payment->update($data);
            $msg = 'تم تحديث سجل الراتب';
        } else {
            $data['recorded_by_user_id'] = auth()->id();
            SalaryPayment::create($data);
            $msg = 'تم تسجيل الراتب بنجاح';
        }

        session()->flash('toast', $msg);
        $this->redirect(route('salary-payments.index'), navigate: true);
    }

    public function render()
    {
        return view('livewire.salary-payment-form', [
            'netPreview' => $this->netAmount(),
        ]);
    }
}
