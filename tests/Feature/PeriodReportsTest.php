<?php

use App\Models\Client;
use App\Models\ClientBalanceAdjustment;
use App\Models\ClientPayment;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\SupplierPayment;
use App\Models\User;
use App\Services\Reports\AggregatedClientStatementsService;
use App\Services\Reports\AggregatedSupplierStatementsService;
use App\Services\Reports\AsOfSummaryFilters;
use App\Services\Reports\CashflowReportService;
use App\Services\Reports\ClientAdjustmentsPeriodReportService;
use App\Services\Reports\ClientPaymentsReportService;
use App\Services\Reports\ClientReceivablesSummaryService;
use App\Services\Reports\ExpensesReportService;
use App\Services\Reports\FinancialPeriodSummaryService;
use App\Services\Reports\ReportPeriodFilters;
use App\Services\Reports\SupplierPaymentsReportService;
use App\Services\Reports\SupplierPayablesSummaryService;
use App\Services\Reports\UnifiedActivityLogService;
use Carbon\Carbon;

beforeEach(function () {
    $this->manager = User::factory()->create(['role' => 'manager', 'is_active' => true]);
    $this->accountant = User::factory()->create(['role' => 'accountant', 'is_active' => true]);
    $this->viewer = User::factory()->create(['role' => 'viewer', 'is_active' => true]);
});

test('reports hub loads for viewer', function () {
    $this->actingAs($this->viewer);
    $this->get(route('reports.index'))->assertOk()->assertSee('مركز التقارير');
});

test('cashflow report aggregates inflows and outflows for period', function () {
    $client = Client::factory()->create();
    $supplier = Supplier::create([
        'legacy_number' => 'rpt-'.uniqid(),
        'business_name' => 'مورد تقرير',
    ]);

    ClientPayment::factory()->create([
        'client_id' => $client->id,
        'amount' => 1000,
        'currency_code' => 'ILS',
        'paid_at' => '2025-05-10 10:00:00',
    ]);

    SupplierPayment::create([
        'supplier_id' => $supplier->id,
        'amount' => 300,
        'currency_code' => 'ILS',
        'paid_at' => '2025-05-12 10:00:00',
        'method' => 'cash',
        'bank_reference' => null,
        'notes' => null,
        'recorded_by_user_id' => null,
    ]);

    Expense::create([
        'description' => 'إيجار',
        'amount' => 200,
        'currency_code' => 'ILS',
        'expense_date' => '2025-05-15',
        'notes' => null,
        'recorded_by_user_id' => $this->accountant->id,
    ]);

    $filters = ReportPeriodFilters::fromArray([
        'date_from' => '2025-05-01',
        'date_to' => '2025-05-31',
    ]);

    $summary = (new CashflowReportService)->summaryByCurrency($filters);

    expect($summary['ILS']['inflow'])->toBe(1000.0);
    expect($summary['ILS']['supplier_outflow'])->toBe(300.0);
    expect($summary['ILS']['expense_outflow'])->toBe(200.0);
    expect($summary['ILS']['net'])->toBe(500.0);
});

test('client payments report filters by date range', function () {
    $client = Client::factory()->create();

    ClientPayment::factory()->create([
        'client_id' => $client->id,
        'amount' => 500,
        'currency_code' => 'USD',
        'paid_at' => '2025-04-01 00:00:00',
    ]);

    ClientPayment::factory()->create([
        'client_id' => $client->id,
        'amount' => 800,
        'currency_code' => 'USD',
        'paid_at' => '2025-06-01 00:00:00',
    ]);

    $filters = ReportPeriodFilters::fromArray([
        'date_from' => '2025-05-01',
        'date_to' => '2025-05-31',
        'currency' => 'USD',
    ]);

    $rows = (new ClientPaymentsReportService)->rows($filters);

    expect($rows)->toHaveCount(0);
});

test('viewer cannot export period report pdf', function () {
    $this->actingAs($this->viewer);
    $this->get(route('reports.cashflow.pdf', [
        'date_from' => now()->startOfMonth()->format('Y-m-d'),
        'date_to' => now()->format('Y-m-d'),
    ]))->assertForbidden();
});

test('accountant can export cashflow pdf', function () {
    $this->actingAs($this->accountant);
    $this->get(route('reports.cashflow.pdf', [
        'date_from' => now()->startOfMonth()->format('Y-m-d'),
        'date_to' => now()->format('Y-m-d'),
    ]))->assertOk()->assertHeader('content-type', 'application/pdf');
});

test('cashflow report page loads for accountant', function () {
    $this->actingAs($this->accountant);
    $this->get(route('reports.cashflow'))
        ->assertOk()
        ->assertSee('كشف التدفق النقدي')
        ->assertSeeLivewire('reports.cashflow-report');
});

test('expenses period report returns rows in range', function () {
    Expense::create([
        'description' => 'وقود',
        'amount' => 150,
        'currency_code' => 'ILS',
        'expense_date' => Carbon::parse('2025-07-01'),
        'notes' => null,
        'recorded_by_user_id' => $this->accountant->id,
    ]);

    $filters = ReportPeriodFilters::fromArray([
        'date_from' => '2025-07-01',
        'date_to' => '2025-07-31',
    ]);

    $rows = (new ExpensesReportService)->rows($filters);

    expect($rows)->toHaveCount(1);
    expect($rows->first()['description'])->toBe('وقود');
});

test('supplier payments report totals by currency', function () {
    $supplier = Supplier::create([
        'legacy_number' => 'rpt2-'.uniqid(),
        'business_name' => 'مورد 2',
    ]);

    SupplierPayment::create([
        'supplier_id' => $supplier->id,
        'amount' => 400,
        'currency_code' => 'EUR',
        'paid_at' => '2025-08-05 00:00:00',
        'method' => 'bank',
        'bank_reference' => 'REF-1',
        'notes' => null,
        'recorded_by_user_id' => null,
    ]);

    $filters = ReportPeriodFilters::fromArray([
        'date_from' => '2025-08-01',
        'date_to' => '2025-08-31',
    ]);

    $totals = (new SupplierPaymentsReportService)->totalsByCurrency($filters);

    expect($totals['EUR'])->toBe(400.0);
});

test('sales period report includes issued invoices only', function () {
    $client = Client::factory()->create();

    Invoice::factory()->create([
        'client_id' => $client->id,
        'currency_code' => 'ILS',
        'total_amount' => 1000,
        'status' => 'issued',
        'document_date' => '2025-09-10',
    ]);

    Invoice::factory()->create([
        'client_id' => $client->id,
        'currency_code' => 'ILS',
        'total_amount' => 500,
        'status' => 'draft',
        'document_date' => '2025-09-11',
    ]);

    $filters = ReportPeriodFilters::fromArray([
        'date_from' => '2025-09-01',
        'date_to' => '2025-09-30',
    ]);

    $rows = (new \App\Services\Reports\SalesPeriodReportService)->rows($filters);
    $totals = (new \App\Services\Reports\SalesPeriodReportService)->totalsByCurrency($filters);

    expect($rows)->toHaveCount(1);
    expect($totals['ILS']['total'])->toBe(1000.0);
    expect($totals['ILS']['count'])->toBe(1);
});

test('purchase orders period report filters by supplier', function () {
    $supplier = Supplier::create([
        'legacy_number' => 'po-rpt-'.uniqid(),
        'business_name' => 'مورد PO',
    ]);

    PurchaseOrder::create([
        'supplier_id' => $supplier->id,
        'legacy_po_no' => 'PO-99',
        'document_date' => '2025-10-05',
        'due_date' => null,
        'currency_code' => 'ILS',
        'discount_amount' => 0,
        'total_amount' => 750,
        'notes' => null,
        'status' => 'issued',
        'recorded_by_user_id' => null,
    ]);

    $filters = ReportPeriodFilters::fromArray([
        'date_from' => '2025-10-01',
        'date_to' => '2025-10-31',
        'supplier_id' => (string) $supplier->id,
    ]);

    $rows = (new \App\Services\Reports\PurchaseOrdersPeriodReportService)->rows($filters);

    expect($rows)->toHaveCount(1);
    expect($rows->first()['reference'])->toBe('PO-99');
});

test('sales report page loads with livewire', function () {
    $this->actingAs($this->accountant);
    $this->get(route('reports.sales'))
        ->assertOk()
        ->assertSee('تقرير المبيعات')
        ->assertSeeLivewire('reports.sales-period-report');
});

test('accountant can export sales pdf', function () {
    $this->actingAs($this->accountant);
    $this->get(route('reports.sales.pdf', [
        'date_from' => now()->startOfMonth()->format('Y-m-d'),
        'date_to' => now()->format('Y-m-d'),
    ]))->assertOk()->assertHeader('content-type', 'application/pdf');
});

test('financial period summary aggregates sales and cash by currency', function () {
    $client = Client::factory()->create();

    Invoice::factory()->create([
        'client_id' => $client->id,
        'currency_code' => 'ILS',
        'total_amount' => 2000,
        'status' => 'issued',
        'document_date' => '2025-11-05',
    ]);

    ClientPayment::factory()->create([
        'client_id' => $client->id,
        'amount' => 1500,
        'currency_code' => 'ILS',
        'paid_at' => '2025-11-10 10:00:00',
    ]);

    Expense::create([
        'description' => 'صيانة',
        'amount' => 100,
        'currency_code' => 'ILS',
        'expense_date' => '2025-11-12',
        'notes' => null,
        'recorded_by_user_id' => $this->accountant->id,
    ]);

    $filters = ReportPeriodFilters::fromArray([
        'date_from' => '2025-11-01',
        'date_to' => '2025-11-30',
    ]);

    $summary = (new FinancialPeriodSummaryService)->byCurrency($filters);

    expect($summary['ILS']['sales'])->toBe(2000.0);
    expect($summary['ILS']['client_payments'])->toBe(1500.0);
    expect($summary['ILS']['expenses'])->toBe(100.0);
    expect($summary['ILS']['net_cash'])->toBe(1400.0);
    expect($summary['ILS']['invoice_count'])->toBe(1);
});

test('unified activity log merges document types chronologically', function () {
    $client = Client::factory()->create();

    Invoice::factory()->create([
        'client_id' => $client->id,
        'currency_code' => 'USD',
        'total_amount' => 500,
        'status' => 'issued',
        'document_date' => '2025-12-01',
    ]);

    ClientPayment::factory()->create([
        'client_id' => $client->id,
        'amount' => 200,
        'currency_code' => 'USD',
        'paid_at' => '2025-12-02 09:00:00',
    ]);

    $filters = ReportPeriodFilters::fromArray([
        'date_from' => '2025-12-01',
        'date_to' => '2025-12-31',
    ]);

    $result = (new UnifiedActivityLogService)->timeline($filters);

    expect($result['rows'])->toHaveCount(2);
    expect($result['rows']->pluck('type')->all())->toContain('invoice', 'client_payment');
    expect($result['truncated'])->toBeFalse();
});

test('client adjustments period report filters by date', function () {
    $client = Client::factory()->create();

    ClientBalanceAdjustment::factory()->create([
        'client_id' => $client->id,
        'amount' => 75,
        'currency_code' => 'ILS',
        'adjustment_date' => '2026-01-10',
    ]);

    ClientBalanceAdjustment::factory()->create([
        'client_id' => $client->id,
        'amount' => 50,
        'currency_code' => 'ILS',
        'adjustment_date' => '2026-02-10',
    ]);

    $filters = ReportPeriodFilters::fromArray([
        'date_from' => '2026-01-01',
        'date_to' => '2026-01-31',
    ]);

    $rows = (new ClientAdjustmentsPeriodReportService)->rows($filters);
    $totals = (new ClientAdjustmentsPeriodReportService)->totalsByCurrency($filters);

    expect($rows)->toHaveCount(1);
    expect($totals['ILS'])->toBe(75.0);
});

test('phase four report pages load with livewire', function () {
    $this->actingAs($this->accountant);

    $this->get(route('reports.financial-period'))
        ->assertOk()
        ->assertSee('لوحة الفترة المالية')
        ->assertSeeLivewire('reports.financial-period-summary-report');

    $this->get(route('reports.activity-log'))
        ->assertOk()
        ->assertSee('سجل النشاط المالي')
        ->assertSeeLivewire('reports.unified-activity-log-report');

    $this->get(route('reports.client-adjustments'))
        ->assertOk()
        ->assertSee('تسويات العملاء')
        ->assertSeeLivewire('reports.client-adjustments-period-report');
});

test('accountant can export phase four pdfs', function () {
    $this->actingAs($this->accountant);
    $params = [
        'date_from' => now()->startOfMonth()->format('Y-m-d'),
        'date_to' => now()->format('Y-m-d'),
    ];

    $this->get(route('reports.financial-period.pdf', $params))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');

    $this->get(route('reports.activity-log.pdf', $params))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');

    $this->get(route('reports.client-adjustments.pdf', $params))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');
});

test('viewer cannot export activity log pdf', function () {
    $this->actingAs($this->viewer);
    $this->get(route('reports.activity-log.pdf', [
        'date_from' => now()->startOfMonth()->format('Y-m-d'),
        'date_to' => now()->format('Y-m-d'),
    ]))->assertForbidden();
});

test('client receivables summary lists balance as of date', function () {
    $client = Client::factory()->create();

    Invoice::factory()->create([
        'client_id' => $client->id,
        'currency_code' => 'ILS',
        'total_amount' => 900,
        'status' => 'issued',
        'document_date' => '2026-03-01',
    ]);

    ClientPayment::factory()->create([
        'client_id' => $client->id,
        'amount' => 200,
        'currency_code' => 'ILS',
        'paid_at' => '2026-03-05 10:00:00',
    ]);

    $filters = AsOfSummaryFilters::fromPeriodFilters(ReportPeriodFilters::fromArray([
        'date_to' => '2026-03-31',
    ]));

    $rows = (new ClientReceivablesSummaryService)->rows($filters);

    expect($rows)->toHaveCount(1);
    expect($rows->first()['balance'])->toBe(700.0);
});

test('supplier payables summary lists balance as of date', function () {
    $supplier = Supplier::create([
        'legacy_number' => 'p5-'.uniqid(),
        'business_name' => 'مورد مرحلة 5',
    ]);

    PurchaseOrder::create([
        'supplier_id' => $supplier->id,
        'legacy_po_no' => 'PO-P5',
        'document_date' => '2026-04-01',
        'due_date' => null,
        'currency_code' => 'USD',
        'discount_amount' => 0,
        'total_amount' => 500,
        'notes' => null,
        'status' => 'issued',
        'recorded_by_user_id' => null,
    ]);

    $filters = AsOfSummaryFilters::fromPeriodFilters(ReportPeriodFilters::fromArray([
        'date_to' => '2026-04-30',
        'currency' => 'USD',
    ]));

    $rows = (new SupplierPayablesSummaryService)->rows($filters);

    expect($rows)->toHaveCount(1);
    expect($rows->first()['balance'])->toBe(500.0);
});

test('aggregated client statements summarize period per client', function () {
    $client = Client::factory()->create();

    Invoice::factory()->create([
        'client_id' => $client->id,
        'currency_code' => 'ILS',
        'total_amount' => 300,
        'status' => 'issued',
        'document_date' => '2026-05-10',
    ]);

    $filters = ReportPeriodFilters::fromArray([
        'date_from' => '2026-05-01',
        'date_to' => '2026-05-31',
    ]);

    $rows = (new AggregatedClientStatementsService)->rows($filters);

    expect($rows)->toHaveCount(1);
    expect($rows->first()['total_invoiced'])->toBe(300.0);
    expect($rows->first()['movement_count'])->toBe(1);
});

test('financial period summary includes receivables and payables snapshots', function () {
    $client = Client::factory()->create();

    Invoice::factory()->create([
        'client_id' => $client->id,
        'currency_code' => 'ILS',
        'total_amount' => 400,
        'status' => 'issued',
        'document_date' => '2026-06-01',
    ]);

    $filters = ReportPeriodFilters::fromArray([
        'date_from' => '2026-06-01',
        'date_to' => '2026-06-30',
    ]);

    $summary = (new FinancialPeriodSummaryService)->byCurrency($filters);

    expect($summary['ILS']['client_receivables'])->toBe(400.0);
    expect($summary['ILS']['sales'])->toBe(400.0);
});

test('phase five report pages load with livewire', function () {
    $this->actingAs($this->accountant);

    $this->get(route('reports.client-receivables-summary'))
        ->assertOk()
        ->assertSee('ملخص ذمم العملاء')
        ->assertSeeLivewire('reports.client-receivables-summary-report');

    $this->get(route('reports.supplier-payables-summary'))
        ->assertOk()
        ->assertSee('ملخص ذمم الموردين')
        ->assertSeeLivewire('reports.supplier-payables-summary-report');

    $this->get(route('reports.aggregated-client-statements'))
        ->assertOk()
        ->assertSee('كشوف العملاء المجمّعة')
        ->assertSeeLivewire('reports.aggregated-client-statements-report');

    $this->get(route('reports.aggregated-supplier-statements'))
        ->assertOk()
        ->assertSee('كشوف الموردين المجمّعة')
        ->assertSeeLivewire('reports.aggregated-supplier-statements-report');
});

test('accountant can export phase five pdfs', function () {
    $this->actingAs($this->accountant);
    $params = [
        'date_from' => now()->startOfMonth()->format('Y-m-d'),
        'date_to' => now()->format('Y-m-d'),
    ];

    $this->get(route('reports.client-receivables-summary.pdf', $params))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');

    $this->get(route('reports.supplier-payables-summary.pdf', $params))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');

    $this->get(route('reports.aggregated-client-statements.pdf', $params))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');

    $this->get(route('reports.aggregated-supplier-statements.pdf', $params))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');
});
