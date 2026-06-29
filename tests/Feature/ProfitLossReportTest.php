<?php

use App\Models\Client;
use App\Models\Employee;
use App\Models\ExchangeRate;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\PurchaseOrder;
use App\Models\SalaryPayment;
use App\Models\Supplier;
use App\Models\User;
use App\Services\Reports\ProfitLossReportService;
use App\Services\Reports\ReportPeriodFilters;

beforeEach(function () {
    $this->manager = User::factory()->create(['role' => 'manager', 'is_active' => true]);
    $this->accountant = User::factory()->create(['role' => 'accountant', 'is_active' => true]);
    $this->viewer = User::factory()->create(['role' => 'viewer', 'is_active' => true]);
});

test('accrual profit loss calculates per currency independently', function () {
    $client = Client::factory()->create();
    $supplier = Supplier::create([
        'legacy_number' => 'pl-'.uniqid(),
        'business_name' => 'مورد P&L',
    ]);

    Invoice::factory()->create([
        'client_id' => $client->id,
        'currency_code' => 'USD',
        'total_amount' => 10,
        'status' => 'issued',
        'document_date' => '2025-05-10',
    ]);

    PurchaseOrder::create([
        'supplier_id' => $supplier->id,
        'currency_code' => 'USD',
        'total_amount' => 9,
        'status' => 'issued',
        'document_date' => '2025-05-11',
        'discount_amount' => 0,
        'notes' => null,
        'recorded_by_user_id' => $this->accountant->id,
    ]);

    $filters = ReportPeriodFilters::fromArray([
        'date_from' => '2025-05-01',
        'date_to' => '2025-05-31',
    ]);

    $rows = (new ProfitLossReportService)->byCurrency($filters, ProfitLossReportService::MODE_ACCRUAL);

    expect($rows['USD']['sales'])->toBe(10.0)
        ->and($rows['USD']['purchases'])->toBe(9.0)
        ->and($rows['USD']['net_profit'])->toBe(1.0);
});

test('cash profit loss uses payments not invoices', function () {
    $client = Client::factory()->create();
    $supplier = Supplier::create([
        'legacy_number' => 'pl-cash-'.uniqid(),
        'business_name' => 'مورد نقدي',
    ]);

    Invoice::factory()->create([
        'client_id' => $client->id,
        'currency_code' => 'USD',
        'total_amount' => 100,
        'status' => 'issued',
        'document_date' => '2025-05-10',
    ]);

    \App\Models\ClientPayment::factory()->create([
        'client_id' => $client->id,
        'amount' => 8,
        'currency_code' => 'USD',
        'paid_at' => '2025-05-15 10:00:00',
    ]);

    \App\Models\SupplierPayment::create([
        'supplier_id' => $supplier->id,
        'amount' => 9,
        'currency_code' => 'USD',
        'paid_at' => '2025-05-16 10:00:00',
        'method' => 'bank',
        'bank_reference' => null,
        'notes' => null,
        'recorded_by_user_id' => $this->accountant->id,
    ]);

    $filters = ReportPeriodFilters::fromArray([
        'date_from' => '2025-05-01',
        'date_to' => '2025-05-31',
    ]);

    $rows = (new ProfitLossReportService)->byCurrency($filters, ProfitLossReportService::MODE_CASH);

    expect($rows['USD']['sales'])->toBe(8.0)
        ->and($rows['USD']['purchases'])->toBe(9.0)
        ->and($rows['USD']['net_profit'])->toBe(-1.0);
});

test('profit loss includes paid salaries from hr module', function () {
    $employee = Employee::factory()->create();

    SalaryPayment::create([
        'employee_id' => $employee->id,
        'period_year' => 2025,
        'period_month' => 5,
        'base_amount' => 1000,
        'bonus_amount' => 0,
        'deduction_amount' => 0,
        'net_amount' => 1000,
        'currency_code' => 'ILS',
        'status' => SalaryPayment::STATUS_PAID,
        'paid_at' => '2025-05-20',
        'method' => 'bank',
        'recorded_by_user_id' => $this->accountant->id,
    ]);

    Expense::create([
        'description' => 'قرطاسية',
        'amount' => 200,
        'currency_code' => 'ILS',
        'expense_date' => '2025-05-10',
        'notes' => null,
        'recorded_by_user_id' => $this->accountant->id,
    ]);

    $filters = ReportPeriodFilters::fromArray([
        'date_from' => '2025-05-01',
        'date_to' => '2025-05-31',
    ]);

    $rows = (new ProfitLossReportService)->byCurrency($filters, ProfitLossReportService::MODE_ACCRUAL);

    expect($rows['ILS']['expenses'])->toBe(200.0)
        ->and($rows['ILS']['salaries'])->toBe(1000.0);
});

test('ils consolidated report uses stored exchange rates', function () {
    ExchangeRate::create([
        'rate_date' => '2025-05-31',
        'currency_code' => 'USD',
        'rate_to_ils' => 3.5,
        'source' => 'BOI',
    ]);

    $client = Client::factory()->create();

    Invoice::factory()->create([
        'client_id' => $client->id,
        'currency_code' => 'USD',
        'total_amount' => 10,
        'status' => 'issued',
        'document_date' => '2025-05-10',
    ]);

    $filters = ReportPeriodFilters::fromArray([
        'date_from' => '2025-05-01',
        'date_to' => '2025-05-31',
    ]);

    $totals = (new ProfitLossReportService)->consolidatedIls($filters, ProfitLossReportService::MODE_ACCRUAL);

    expect($totals['sales'])->toBe(35.0)
        ->and($totals['rates']['USD'])->toBe(3.5);
});

test('viewer can open profit loss report page', function () {
    $this->actingAs($this->viewer);
    $this->get(route('reports.profit-loss'))->assertOk()->assertSee('قائمة الربح والخسارة');
});

test('viewer cannot export profit loss pdf', function () {
    $this->actingAs($this->viewer);
    $this->get(route('reports.profit-loss.pdf'))->assertForbidden();
});
