<?php

use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderLine;
use App\Models\Supplier;
use App\Models\SupplierBalanceAdjustment;
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
        'description' => 'ورق A4 و أحبار',
        'unit_price' => 1200,
        'quantity' => 1,
        'line_total' => 1200,
    ]);

    $this->actingAs($this->accountant);
    $this->get(route('suppliers.statement', $supplier))
        ->assertOk()
        ->assertSee('إجمالي أوامر الشراء')
        ->assertSee('إجمالي الدفعات')
        ->assertSee('المتبقي للمورد')
        ->assertSee('حركة الحساب')
        ->assertSee('PO-100')
        ->assertSee('مستلزمات طباعة')
        ->assertSee('ورق A4 و أحبار')
        ->assertSee('التفاصيل')
        ->assertSee('+1,200.00')
        ->assertSee('عرض')
        ->assertSee('طباعة')
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

    expect($rows[1][2])->toContain('أمر شراء');
    expect($rows[1][3])->toBe('+300.00');
});

test('supplier statement csv rows match statement totals', function () {
    $supplier = Supplier::create([
        'legacy_number' => 'st-'.uniqid(),
        'business_name' => 'مورد CSV كامل',
    ]);

    PurchaseOrder::create([
        'supplier_id' => $supplier->id,
        'legacy_po_no' => 'PO-CSV',
        'document_date' => '2025-03-01',
        'due_date' => null,
        'currency_code' => 'ILS',
        'discount_amount' => 0,
        'total_amount' => 2500,
        'notes' => null,
        'status' => 'issued',
        'recorded_by_user_id' => null,
    ]);

    SupplierPayment::create([
        'supplier_id' => $supplier->id,
        'amount' => 1000,
        'currency_code' => 'ILS',
        'paid_at' => '2025-03-15 00:00:00',
        'method' => 'bank',
        'bank_reference' => null,
        'notes' => null,
        'recorded_by_user_id' => null,
    ]);

    SupplierBalanceAdjustment::create([
        'supplier_id' => $supplier->id,
        'amount' => 200,
        'currency_code' => 'ILS',
        'adjustment_date' => '2025-03-20',
        'type' => SupplierBalanceAdjustment::TYPE_SETTLEMENT_DISCOUNT,
        'reason' => 'خصم اتفاق',
        'recorded_by_user_id' => null,
    ]);

    $service = new SupplierStatementService;
    $statement = $service->forSupplier($supplier);
    $rows = $service->toCsvRows($statement);

    expect($rows[0])->toContain('العملة');
    expect($rows[1][3])->toBe('+2500.00');
    expect($rows[2][3])->toBe('-1000.00');
    expect($rows[3][3])->toBe('-200.00');
    expect($rows[4][3])->toBe('2500.00');
    expect($rows[5][3])->toBe('1000.00');
    expect($rows[6][3])->toBe('200.00');
    expect($rows[7][2])->toContain('المتبقي للمورد');
    expect($rows[7][3])->toBe('1300.00');
});

test('supplier statement pdf uses timeline with purchase order lines and adjustments', function () {
    $supplier = Supplier::create([
        'legacy_number' => 'st-'.uniqid(),
        'business_name' => 'مورد PDF تفاصيل',
    ]);

    $po = PurchaseOrder::create([
        'supplier_id' => $supplier->id,
        'legacy_po_no' => 'PO-PDF',
        'document_date' => '2025-06-01',
        'due_date' => null,
        'currency_code' => 'USD',
        'discount_amount' => 0,
        'total_amount' => 1600,
        'notes' => null,
        'status' => 'issued',
        'recorded_by_user_id' => null,
    ]);

    PurchaseOrderLine::query()->create([
        'purchase_order_id' => $po->id,
        'line_order' => 1,
        'title' => 'طباعة بanners',
        'description' => 'طباعة لافتات خارجية',
        'unit_price' => 1600,
        'quantity' => 1,
        'line_total' => 1600,
    ]);

    SupplierBalanceAdjustment::create([
        'supplier_id' => $supplier->id,
        'amount' => 100,
        'currency_code' => 'USD',
        'adjustment_date' => '2025-06-05',
        'type' => SupplierBalanceAdjustment::TYPE_SETTLEMENT_DISCOUNT,
        'reason' => 'خصم',
        'recorded_by_user_id' => null,
    ]);

    $statement = (new SupplierStatementService)->forSupplier($supplier);

    $html = view('pdf.supplier-statement', [
        'supplier' => $supplier,
        'statement' => $statement,
        'dateFrom' => null,
        'dateTo' => null,
    ])->render();

    expect($html)->toContain('حركة الحساب');
    expect($html)->toContain('PO-PDF');
    expect($html)->toContain('طباعة بanners');
    expect($html)->toContain('طباعة لافتات خارجية');
    expect($html)->toContain('1,600.00 USD');
    expect($html)->toContain('تسوية');
});

test('soft deleted supplier payment is excluded from statement', function () {
    $supplier = Supplier::create([
        'legacy_number' => 'st-'.uniqid(),
        'business_name' => 'مورد حذف',
    ]);

    PurchaseOrder::create([
        'supplier_id' => $supplier->id,
        'legacy_po_no' => 'PO-DEL',
        'document_date' => now()->format('Y-m-d'),
        'due_date' => null,
        'currency_code' => 'ILS',
        'discount_amount' => 0,
        'total_amount' => 1000,
        'notes' => null,
        'status' => 'issued',
        'recorded_by_user_id' => null,
    ]);

    $payment = SupplierPayment::create([
        'supplier_id' => $supplier->id,
        'amount' => 400,
        'currency_code' => 'ILS',
        'paid_at' => now(),
        'method' => 'cash',
        'bank_reference' => null,
        'notes' => null,
        'recorded_by_user_id' => null,
    ]);

    $payment->delete();

    $statement = (new SupplierStatementService)->forSupplier($supplier);

    expect((float) $statement['ILS']['total_paid'])->toBe(0.0);
    expect((float) $statement['ILS']['balance'])->toBe(1000.0);
});

test('supplier currencies never mix', function () {
    $supplier = Supplier::create([
        'legacy_number' => 'st-'.uniqid(),
        'business_name' => 'مورد عملات',
    ]);

    PurchaseOrder::create([
        'supplier_id' => $supplier->id,
        'legacy_po_no' => 'PO-ILS',
        'document_date' => now()->format('Y-m-d'),
        'due_date' => null,
        'currency_code' => 'ILS',
        'discount_amount' => 0,
        'total_amount' => 5000,
        'notes' => null,
        'status' => 'issued',
        'recorded_by_user_id' => null,
    ]);

    SupplierPayment::create([
        'supplier_id' => $supplier->id,
        'amount' => 100,
        'currency_code' => 'USD',
        'paid_at' => now(),
        'method' => 'cash',
        'bank_reference' => null,
        'notes' => null,
        'recorded_by_user_id' => null,
    ]);

    $statement = (new SupplierStatementService)->forSupplier($supplier);

    expect((float) $statement['ILS']['balance'])->toBe(5000.0);
    expect((float) $statement['USD']['balance'])->toBe(-100.0);
});

test('supplier statement shows payment reference from notes when bank reference empty', function () {
    $supplier = Supplier::create([
        'legacy_number' => 'st-'.uniqid(),
        'business_name' => 'مورد مرجع',
    ]);

    PurchaseOrder::create([
        'supplier_id' => $supplier->id,
        'legacy_po_no' => 'PO-REF',
        'document_date' => '2025-05-01',
        'due_date' => null,
        'currency_code' => 'ILS',
        'discount_amount' => 0,
        'total_amount' => 1000,
        'notes' => null,
        'status' => 'issued',
        'recorded_by_user_id' => null,
    ]);

    SupplierPayment::create([
        'supplier_id' => $supplier->id,
        'amount' => 500,
        'currency_code' => 'ILS',
        'paid_at' => '2025-05-10 00:00:00',
        'method' => 'cash',
        'bank_reference' => null,
        'notes' => 'CHK-SUP-7788',
        'recorded_by_user_id' => null,
    ]);

    $this->actingAs($this->accountant);
    $this->get(route('suppliers.statement', $supplier))
        ->assertOk()
        ->assertSee('دفعة CHK-SUP-7788')
        ->assertDontSee('دفعة #');
});

test('supplier statement shows purchase order line description in details column', function () {
    $supplier = Supplier::create([
        'legacy_number' => 'st-'.uniqid(),
        'business_name' => 'مورد بنود',
    ]);

    $po = PurchaseOrder::create([
        'supplier_id' => $supplier->id,
        'legacy_po_no' => 'PO-DESC',
        'document_date' => '2025-06-15',
        'due_date' => null,
        'currency_code' => 'ILS',
        'discount_amount' => 0,
        'total_amount' => 900,
        'notes' => null,
        'status' => 'issued',
        'recorded_by_user_id' => null,
    ]);

    PurchaseOrderLine::query()->create([
        'purchase_order_id' => $po->id,
        'line_order' => 1,
        'title' => 'حبر طباعة',
        'description' => 'حبر CMYK — عبوة 1 لتر',
        'unit_price' => 900,
        'quantity' => 1,
        'line_total' => 900,
    ]);

    $this->actingAs($this->accountant);
    $this->get(route('suppliers.statement', $supplier))
        ->assertOk()
        ->assertSee('حبر طباعة')
        ->assertSee('حبر CMYK — عبوة 1 لتر')
        ->assertSee('التفاصيل');
});
