<?php

namespace App\Livewire;

use App\Livewire\Concerns\WithPendingReceivedCheckSelection;
use App\Models\Employee;
use App\Models\Product;
use App\Models\ReceivedCheck;
use App\Models\SalaryAdvance;
use App\Services\Finance\PaymentMethod;
use App\Services\Finance\ReceivedCheckEndorsementService;
use Livewire\Component;

class SalaryAdvanceForm extends Component
{
    use WithPendingReceivedCheckSelection;

    public ?int $recordId = null;

    public string $employee_id = '';

    public string $amount = '0';

    public string $currency_code = 'ILS';

    public string $paid_at = '';

    public string $method = 'cash';

    public string $bank_reference = '';

    public string $status = SalaryAdvance::STATUS_PAID;

    public string $notes = '';

    /** @var array<int, string> */
    public array $employeeOptions = [];

    /** @var list<string> */
    public array $currencyOptions = [];

    public function mount(?SalaryAdvance $salaryAdvance = null): void
    {
        $this->currencyOptions = Product::billingCurrencies();
        $this->employeeOptions = Employee::query()
            ->where('is_active', true)
            ->orderBy('full_name')
            ->pluck('full_name', 'id')
            ->all();

        if ($salaryAdvance && $salaryAdvance->exists) {
            $this->authorize('update', $salaryAdvance);
            $this->recordId = $salaryAdvance->id;
            $this->employee_id = (string) $salaryAdvance->employee_id;
            $this->amount = (string) $salaryAdvance->amount;
            $this->currency_code = $salaryAdvance->currency_code;
            $this->paid_at = $salaryAdvance->paid_at?->format('Y-m-d') ?? '';
            $this->method = $salaryAdvance->method ?? 'cash';
            $this->bank_reference = $salaryAdvance->bank_reference ?? '';
            $this->status = $salaryAdvance->status;
            $this->notes = $salaryAdvance->notes ?? '';
        } else {
            $this->authorize('create', SalaryAdvance::class);
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

        $this->currency_code = $employee->base_salary_currency ?? 'ILS';
    }

    public function updatedCurrencyCode(): void
    {
        if ($this->check_source === 'register') {
            $this->received_check_id = '';
        }
    }

    public function updatedAmount(): void
    {
        $this->reconcileRegisterCheckWithFilterAmount();
    }

    protected function pendingCheckFilterAmount(): ?float
    {
        if ($this->amount === '' || ! is_numeric($this->amount)) {
            return null;
        }

        return (float) $this->amount;
    }

    public function updatedStatus(): void
    {
        if ($this->status !== SalaryAdvance::STATUS_PAID) {
            $this->resetPendingCheckSelection();
        }
    }

    public function canSelectPendingCheck(): bool
    {
        return ! $this->recordId
            && $this->isCheckPaymentMethod()
            && $this->status === SalaryAdvance::STATUS_PAID;
    }

    protected function applyPendingCheckToForm(ReceivedCheck $check): void
    {
        $this->currency_code = $check->currency_code ?? 'ILS';
        $this->amount = number_format((float) $check->amount, 2, '.', '');
        $this->bank_reference = $check->check_number;
        $this->method = PaymentMethod::CHECK;
        $this->check_source = 'register';
    }

    public function save(): void
    {
        $usingRegisterCheck = $this->canSelectPendingCheck()
            && $this->check_source === 'register';

        $this->validate([
            'employee_id' => 'required|exists:employees,id',
            'amount' => 'required|numeric|min:0.01',
            'currency_code' => 'required|string|size:3|in:'.implode(',', $this->currencyOptions),
            'paid_at' => 'required|date',
            'method' => 'required|in:cash,bank,check,transfer',
            'status' => 'required|in:paid,cancelled',
            'bank_reference' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ], [], [
            'employee_id' => 'الموظف',
            'amount' => 'المبلغ',
            'paid_at' => 'تاريخ الدفع',
            'method' => 'طريقة الدفع',
        ]);

        if ($usingRegisterCheck) {
            $this->validate([
                'received_check_id' => 'required|integer|exists:received_checks,id',
            ], [], [
                'received_check_id' => 'الشيك',
            ]);
        }

        if ($usingRegisterCheck) {
            $check = $this->selectedPendingCheck();
            if (! $check) {
                $this->addError('received_check_id', 'الشيك غير متاح للتظهير.');

                return;
            }

            if ($check->currency_code !== $this->currency_code) {
                $this->addError('received_check_id', 'عملة الشيك لا تطابق عملة السلفة.');

                return;
            }

            if (abs(round((float) $check->amount, 2) - round((float) $this->amount, 2)) > 0.009) {
                $this->addError('amount', 'مبلغ السلفة يجب أن يساوي مبلغ الشيك المختار.');

                return;
            }

            try {
                app(ReceivedCheckEndorsementService::class)->endorseToEmployee(
                    $check->fresh(),
                    (int) $this->employee_id,
                    'advance',
                    null,
                    null,
                    auth()->id(),
                );
            } catch (\InvalidArgumentException $e) {
                $this->addError('received_check_id', $e->getMessage());

                return;
            }

            session()->flash('toast', 'تم تسجيل السلفة وتظهير الشيك للموظف');
            $this->redirect(route('salary-advances.index'), navigate: true);

            return;
        }

        $data = [
            'employee_id' => (int) $this->employee_id,
            'amount' => $this->amount,
            'currency_code' => $this->currency_code,
            'paid_at' => $this->paid_at,
            'method' => $this->method,
            'bank_reference' => $this->bank_reference !== '' ? $this->bank_reference : null,
            'status' => $this->status,
            'notes' => $this->notes !== '' ? $this->notes : null,
        ];

        if ($this->recordId) {
            $advance = SalaryAdvance::findOrFail($this->recordId);
            $this->authorize('update', $advance);
            $advance->update($data);
            $msg = 'تم تحديث السلفة';
        } else {
            $data['recorded_by_user_id'] = auth()->id();
            SalaryAdvance::create($data);
            $msg = 'تم تسجيل السلفة بنجاح';
        }

        session()->flash('toast', $msg);
        $this->redirect(route('salary-advances.index'), navigate: true);
    }

    public function render()
    {
        $pendingChecks = $this->pendingChecksForSelect(
            $this->currency_code !== '' ? $this->currency_code : null,
        );

        $filterAmount = $this->resolvePendingCheckFilterAmount();

        return view('livewire.salary-advance-form', [
            'pendingChecks' => $pendingChecks,
            'selectedCheck' => $this->selectedPendingCheck(),
            'canSelectPendingCheck' => $this->canSelectPendingCheck(),
            'isCheckPayment' => $this->isCheckPaymentMethod(),
            'currencyFilter' => $this->currency_code !== '',
            'amountFilter' => $filterAmount !== null,
        ]);
    }
}
