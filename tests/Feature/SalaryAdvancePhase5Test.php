<?php

use App\Livewire\SalaryAdvanceForm;
use App\Livewire\SalaryPaymentForm;
use App\Models\Employee;
use App\Models\SalaryAdvance;
use App\Models\SalaryPayment;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->accountant = User::factory()->create(['role' => 'accountant', 'is_active' => true]);
    $this->viewer = User::factory()->create(['role' => 'viewer', 'is_active' => true]);
});

test('accountant can register salary advance', function () {
    $employee = Employee::factory()->create(['full_name' => 'محمد سلف']);

    $this->actingAs($this->accountant);

    Livewire::test(SalaryAdvanceForm::class)
        ->set('employee_id', (string) $employee->id)
        ->set('amount', '1500')
        ->set('currency_code', 'ILS')
        ->set('paid_at', '2025-06-10')
        ->set('method', 'cash')
        ->set('status', SalaryAdvance::STATUS_PAID)
        ->call('save')
        ->assertRedirect(route('salary-advances.index'));

    $advance = SalaryAdvance::where('employee_id', $employee->id)->first();
    expect($advance)->not->toBeNull()
        ->and((float) $advance->amount)->toBe(1500.0)
        ->and($advance->status)->toBe(SalaryAdvance::STATUS_PAID);
});

test('viewer can list advances but not create', function () {
    $employee = Employee::factory()->create();
    SalaryAdvance::create([
        'employee_id' => $employee->id,
        'amount' => 500,
        'currency_code' => 'ILS',
        'paid_at' => '2025-05-01',
        'method' => 'cash',
        'status' => SalaryAdvance::STATUS_PAID,
        'recorded_by_user_id' => $this->accountant->id,
    ]);

    $this->actingAs($this->viewer);
    $this->get(route('salary-advances.index'))->assertOk();
    $this->get(route('salary-advances.create'))->assertForbidden();
});

test('paid salary with deduction settles advance fifo', function () {
    $employee = Employee::factory()->create([
        'base_salary_amount' => 5000,
        'base_salary_currency' => 'ILS',
    ]);

    SalaryAdvance::create([
        'employee_id' => $employee->id,
        'amount' => 800,
        'currency_code' => 'ILS',
        'paid_at' => '2025-04-01',
        'method' => 'cash',
        'status' => SalaryAdvance::STATUS_PAID,
        'recorded_by_user_id' => $this->accountant->id,
    ]);

    $this->actingAs($this->accountant);

    Livewire::test(SalaryPaymentForm::class)
        ->set('employee_id', (string) $employee->id)
        ->set('period_year', '2025')
        ->set('period_month', '6')
        ->set('base_amount', '5000')
        ->set('deduction_amount', '800')
        ->set('currency_code', 'ILS')
        ->set('status', SalaryPayment::STATUS_PAID)
        ->set('paid_at', '2025-06-25')
        ->set('method', 'bank')
        ->call('save')
        ->assertRedirect(route('salary-payments.index'));

    $advance = SalaryAdvance::where('employee_id', $employee->id)->first();
    expect($advance->status)->toBe(SalaryAdvance::STATUS_SETTLED);
    expect($advance->settled_salary_payment_id)->not->toBeNull();

    $payment = SalaryPayment::where('employee_id', $employee->id)->first();
    expect((float) $payment->net_amount)->toBe(4200.0);
});

test('suggest deduction fills unsettled advance total', function () {
    $employee = Employee::factory()->create(['base_salary_currency' => 'ILS']);

    SalaryAdvance::create([
        'employee_id' => $employee->id,
        'amount' => 600,
        'currency_code' => 'ILS',
        'paid_at' => '2025-05-01',
        'method' => 'cash',
        'status' => SalaryAdvance::STATUS_PAID,
        'recorded_by_user_id' => $this->accountant->id,
    ]);

    $this->actingAs($this->accountant);

    Livewire::test(SalaryPaymentForm::class)
        ->set('employee_id', (string) $employee->id)
        ->call('suggestDeductionFromAdvances')
        ->assertSet('deduction_amount', '600');
});

test('settled advance cannot be edited', function () {
    $employee = Employee::factory()->create();
    $advance = SalaryAdvance::create([
        'employee_id' => $employee->id,
        'amount' => 300,
        'currency_code' => 'ILS',
        'paid_at' => '2025-05-01',
        'method' => 'cash',
        'status' => SalaryAdvance::STATUS_SETTLED,
        'recorded_by_user_id' => $this->accountant->id,
    ]);

    $this->actingAs($this->accountant);
    $this->get(route('salary-advances.edit', $advance))->assertForbidden();
});

test('employee profile shows advances section', function () {
    $employee = Employee::factory()->create(['full_name' => 'ليلى موظفة']);
    SalaryAdvance::create([
        'employee_id' => $employee->id,
        'amount' => 400,
        'currency_code' => 'ILS',
        'paid_at' => '2025-05-15',
        'method' => 'bank',
        'status' => SalaryAdvance::STATUS_PAID,
        'recorded_by_user_id' => $this->accountant->id,
    ]);

    $this->actingAs($this->viewer);
    $this->get(route('employees.show', $employee))
        ->assertOk()
        ->assertSee('سجل السلف')
        ->assertSee('400.00');
});
