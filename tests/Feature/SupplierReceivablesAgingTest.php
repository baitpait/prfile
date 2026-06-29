<?php

use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\SupplierBalanceAdjustment;
use App\Models\User;
use App\Services\Reports\ReportPeriodFilters;
use App\Services\Reports\SupplierAdjustmentsPeriodReportService;
use App\Services\SupplierReceivablesAgingService;

beforeEach(function () {
    $this->accountant = User::factory()->create(['role' => 'accountant', 'is_active' => true]);
    $this->viewer = User::factory()->create(['role' => 'viewer', 'is_active' => true]);
});

test('supplier payables aging shows balance per supplier and currency', function () {
    $supplier = Supplier::create([
        'legacy_number' => 'sa-'.uniqid(),
        'business_name' => 'مورد ذمم',
    ]);

    PurchaseOrder::create([
        'supplier_id' => $supplier->id,
        'legacy_po_no' => 'PO-500',
        'document_date' => '2025-01-10',
        'due_date' => null,
        'currency_code' => 'ILS',
        'discount_amount' => 0,
        'total_amount' => 2000,
        'notes' => null,
        'status' => 'issued',
        'recorded_by_user_id' => null,
    ]);

    $rows = (new SupplierReceivablesAgingService)->rows();

    $match = $rows->first(fn (array $r) => $r['supplier_id'] === $supplier->id && $r['currency_code'] === 'ILS');

    expect($match)->not->toBeNull();
    expect($match['balance'])->toBe(2000.0);
    expect($match['days_from_first_unpaid'])->toBeGreaterThan(0);
});

test('supplier adjustments period report filters by date', function () {
    $supplier = Supplier::create([
        'legacy_number' => 'adj-'.uniqid(),
        'business_name' => 'مورد تسوية',
    ]);

    SupplierBalanceAdjustment::create([
        'supplier_id' => $supplier->id,
        'amount' => 100,
        'currency_code' => 'ILS',
        'adjustment_date' => '2025-11-05',
        'type' => SupplierBalanceAdjustment::TYPE_SETTLEMENT_DISCOUNT,
        'reason' => 'خصم',
        'recorded_by_user_id' => $this->accountant->id,
    ]);

    $filters = ReportPeriodFilters::fromArray([
        'date_from' => '2025-11-01',
        'date_to' => '2025-11-30',
    ]);

    $rows = (new SupplierAdjustmentsPeriodReportService)->rows($filters);

    expect($rows)->toHaveCount(1);
    expect($rows->first()['amount'])->toBe(100.0);
});

test('supplier receivables aging page loads', function () {
    $this->actingAs($this->viewer);
    $this->get(route('reports.supplier-receivables-aging'))
        ->assertOk()
        ->assertSee('أعمار ذمم الموردين')
        ->assertSeeLivewire('reports.supplier-receivables-aging-report');
});

test('accountant can export supplier aging pdf', function () {
    $this->actingAs($this->accountant);
    $this->get(route('reports.supplier-receivables-aging.pdf'))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');
});
