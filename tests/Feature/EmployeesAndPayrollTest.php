<?php

use App\Models\Employee;
use App\Models\SalaryPayment;
use App\Models\User;
use App\Services\Hr\SalaryPeriodReportService;
use App\Services\Reports\ReportPeriodFilters;
use Livewire\Livewire;

beforeEach(function () {
    $this->manager = User::factory()->create(['role' => 'manager', 'is_active' => true]);
    $this->accountant = User::factory()->create(['role' => 'accountant', 'is_active' => true]);
    $this->viewer = User::factory()->create(['role' => 'viewer', 'is_active' => true]);
});

test('viewer can list employees but not create', function () {
    Employee::factory()->create(['full_name' => 'أحمد علي']);

    $this->actingAs($this->viewer);
    $this->get(route('employees.index'))->assertOk()->assertSee('أحمد علي');
    $this->get(route('employees.create'))->assertForbidden();
});

test('manager can create employee via livewire', function () {
    $this->actingAs($this->manager);

    Livewire::test(\App\Livewire\EmployeeForm::class)
        ->set('full_name', 'سارة محمود')
        ->set('base_salary_amount', '5000')
        ->set('base_salary_currency', 'ILS')
        ->set('pay_frequency', Employee::PAY_FREQUENCY_PART_TIME)
        ->call('save')
        ->assertRedirect(route('employees.index'));

    $employee = Employee::where('full_name', 'سارة محمود')->first();
    expect($employee)->not->toBeNull()
        ->and($employee->pay_frequency)->toBe(Employee::PAY_FREQUENCY_PART_TIME);
});

test('accountant can record salary payment', function () {
    $employee = Employee::factory()->create([
        'full_name' => 'خالد يوسف',
        'base_salary_amount' => 4000,
        'base_salary_currency' => 'ILS',
    ]);

    $this->actingAs($this->accountant);

    Livewire::test(\App\Livewire\SalaryPaymentForm::class)
        ->set('employee_id', (string) $employee->id)
        ->set('period_year', '2025')
        ->set('period_month', '5')
        ->set('base_amount', '4000')
        ->set('currency_code', 'ILS')
        ->set('status', SalaryPayment::STATUS_PAID)
        ->set('paid_at', '2025-05-25')
        ->set('method', 'bank')
        ->call('save')
        ->assertRedirect(route('salary-payments.index'));

    $payment = SalaryPayment::where('employee_id', $employee->id)->first();
    expect($payment)->not->toBeNull()
        ->and((float) $payment->net_amount)->toBe(4000.0)
        ->and($payment->recorded_by_user_id)->toBe($this->accountant->id);
});

test('duplicate salary for same employee month currency is rejected', function () {
    $employee = Employee::factory()->create();

    SalaryPayment::create([
        'employee_id' => $employee->id,
        'period_year' => 2025,
        'period_month' => 5,
        'base_amount' => 3000,
        'bonus_amount' => 0,
        'deduction_amount' => 0,
        'net_amount' => 3000,
        'currency_code' => 'ILS',
        'status' => SalaryPayment::STATUS_DRAFT,
        'recorded_by_user_id' => $this->accountant->id,
    ]);

    $this->actingAs($this->accountant);

    Livewire::test(\App\Livewire\SalaryPaymentForm::class)
        ->set('employee_id', (string) $employee->id)
        ->set('period_year', '2025')
        ->set('period_month', '5')
        ->set('base_amount', '3000')
        ->set('currency_code', 'ILS')
        ->call('save')
        ->assertHasErrors(['employee_id']);
});

test('salary period report aggregates paid rows by currency', function () {
    $employee = Employee::factory()->create(['full_name' => 'موظف تقرير']);

    SalaryPayment::create([
        'employee_id' => $employee->id,
        'period_year' => 2025,
        'period_month' => 5,
        'base_amount' => 2000,
        'bonus_amount' => 500,
        'deduction_amount' => 200,
        'net_amount' => 2300,
        'currency_code' => 'ILS',
        'status' => SalaryPayment::STATUS_PAID,
        'paid_at' => '2025-05-20',
        'method' => 'bank',
        'recorded_by_user_id' => $this->accountant->id,
    ]);

    $filters = ReportPeriodFilters::fromArray([
        'date_from' => '2025-05-01',
        'date_to' => '2025-05-31',
    ]);

    $totals = (new SalaryPeriodReportService)->totalsByCurrency($filters);

    expect($totals['ILS']['net'])->toBe(2300.0)
        ->and($totals['ILS']['count'])->toBe(1);
});

test('viewer can open salaries report page', function () {
    $this->actingAs($this->viewer);
    $this->get(route('reports.salaries'))->assertOk()->assertSee('تقرير الرواتب');
});

test('viewer cannot export salaries pdf', function () {
    $this->actingAs($this->viewer);
    $this->get(route('reports.salaries.pdf'))->assertForbidden();
});
