<?php

use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderLine;
use App\Models\Supplier;
use App\Models\SupplierPayment;
use App\Models\User;
use App\Services\SupplierStatementService;

beforeEach(function () {
    $this->viewer = User::factory()->create(['role' => 'viewer', 'is_active' => true]);
    $this->accountant = User::factory()->create(['role' => 'accountant', 'is_active' => true]);
});

test('supplier statement shows balance per currency', function () {
    $supplier = Supplier::create([
        'legacy_number' => 'st-'.uniqid(),
        'business_name' => 'مورد تجريبي',
    ]);

    PurchaseOrder::create([
        'supplier_id' => $supplier->id,
        'legacy_po_no' => 'PO-1',
        'document_date' => '2025-01-05',
        'due_date' => null,
        'currency_code' => 'ILS',
        'discount_amount' => 0,
        'total_amount' => 600,
        'notes' => null,
        'status' => 'issued',
        'recorded_by_user_id' => null,
    ]);

    PurchaseOrder::create([
        'supplier_id' => $supplier->id,
        'legacy_po_no' => 'PO-2',
        'document_date' => '2025-01-06',
        'due_date' => null,
        'currency_code' => 'USD',
        'discount_amount' => 0,
        'total_amount' => 100,
        'notes' => null,
        'status' => 'issued',
        'recorded_by_user_id' => null,
    ]);

    SupplierPayment::create([
        'supplier_id' => $supplier->id,
        'amount' => 100,
        'currency_code' => 'ILS',
        'paid_at' => '2025-02-01 10:00:00',
        'method' => null,
        'bank_reference' => null,
        'notes' => null,
        'recorded_by_user_id' => null,
    ]);

    $service = new SupplierStatementService;
    $statement = $service->forSupplier($supplier);

    expect($statement)->toHaveKeys(['ILS', 'USD']);
    expect((float) $statement['ILS']['balance'])->toBe(500.0);
    expect((float) $statement['USD']['balance'])->toBe(100.0);
});

test('supplier statement page loads for authenticated user', function () {
    $supplier = Supplier::create([
        'legacy_number' => 'st-'.uniqid(),
        'business_name' => 'مورد للواجهة',
    ]);

    $this->actingAs($this->viewer);
    $this->get(route('suppliers.statement', $supplier))->assertOk();
});

test('supplier statement page shows purchase order line details and actions', function () {
    $supplier = Supplier::create([
        'legacy_number' => 'st-'.uniqid(),
        'business_name' => 'مورد تفاصيل',
    ]);

    $po = PurchaseOrder::create([
        'supplier_id' => $supplier->id,
        'legacy_po_no' => 'PO-100',
        'document_date' => '2025-06-01',
        'due_date' => null,
        'currency_code' => 'ILS',
        'discount_amount' => 0,
        'total_amount' => 1200,
        'notes' => null,
        'status' => 'issued',
        'recorded_by_user_id' => null,
    ]);

    PurchaseOrderLine::query()->create([
        'purchase_order_id' => $po->id,
        'line_order' => 1,
        'title' => 'مستلزمات طباعة',
        'unit_price' => 1200,
        'quantity' => 1,
        'line_total' => 1200,
    ]);

    $this->actingAs($this->accountant);
    $this->get(route('suppliers.statement', $supplier))
        ->assertOk()
        ->assertSee('حركة الحساب')
        ->assertSee('PO-100')
        ->assertSee('مستلزمات طباعة')
        ->assertSee('عرض')
        ->assertSee('تعديل');
});

test('viewer cannot download supplier statement pdf', function () {
    $supplier = Supplier::create([
        'legacy_number' => 'st-'.uniqid(),
        'business_name' => 'مورد PDF',
    ]);

    $this->actingAs($this->viewer);
    $this->get(route('suppliers.statement.pdf', $supplier))->assertForbidden();
});

test('accountant can download supplier statement pdf', function () {
    $supplier = Supplier::create([
        'legacy_number' => 'st-'.uniqid(),
        'business_name' => 'مورد PDF محاسب',
    ]);

    PurchaseOrder::create([
        'supplier_id' => $supplier->id,
        'legacy_po_no' => 'P-PDF',
        'document_date' => '2025-04-01',
        'due_date' => null,
        'currency_code' => 'ILS',
        'discount_amount' => 0,
        'total_amount' => 50,
        'notes' => null,
        'status' => 'issued',
        'recorded_by_user_id' => null,
    ]);

    $this->actingAs($this->accountant);
    $this->get(route('suppliers.statement.pdf', $supplier))
        ->assertOk();
});

test('supplier statement csv rows use purchase order type label', function () {
    $supplier = Supplier::create([
        'legacy_number' => 'st-'.uniqid(),
        'business_name' => 'مورد CSV',
    ]);

    PurchaseOrder::create([
        'supplier_id' => $supplier->id,
        'legacy_po_no' => 'X-9',
        'document_date' => '2025-03-01',
        'due_date' => null,
        'currency_code' => 'ILS',
        'discount_amount' => 0,
        'total_amount' => 300,
        'notes' => null,
        'status' => 'issued',
        'recorded_by_user_id' => null,
    ]);

    $service = new SupplierStatementService;
    $statement = $service->forSupplier($supplier);
    $rows = $service->toCsvRows($statement);

    expect($rows[1][1])->toBe('أمر شراء');
});
