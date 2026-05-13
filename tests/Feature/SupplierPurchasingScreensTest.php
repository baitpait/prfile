<?php

use App\Livewire\PurchaseOrderForm;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\SupplierPayment;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->viewer = User::factory()->create(['role' => 'viewer', 'is_active' => true]);
});

test('purchase orders index loads for authenticated user', function () {
    $this->actingAs($this->viewer);
    $this->get(route('purchase-orders.index'))->assertOk();
});

test('supplier payments index loads for authenticated user', function () {
    $this->actingAs($this->viewer);
    $this->get(route('supplier-payments.index'))->assertOk();
});

test('purchase order show loads when purchase order exists', function () {
    $supplier = Supplier::create([
        'legacy_number' => 'po-t-'.uniqid(),
        'business_name' => 'مورد تجريبي للمشتريات',
    ]);

    $po = PurchaseOrder::create([
        'supplier_id' => $supplier->id,
        'legacy_po_no' => 'PO-T-'.uniqid(),
        'document_date' => '2025-06-01',
        'due_date' => null,
        'currency_code' => 'ILS',
        'discount_amount' => 0,
        'total_amount' => 100,
        'notes' => null,
        'status' => 'issued',
        'recorded_by_user_id' => null,
    ]);

    $this->actingAs($this->viewer);
    $this->get(route('purchase-orders.show', $po))->assertOk();
});

test('accountant can create purchase order from form page', function () {
    $accountant = User::factory()->create(['role' => 'accountant', 'is_active' => true]);
    $supplier = Supplier::create([
        'legacy_number' => 'po-create-'.uniqid(),
        'business_name' => 'مورد إنشاء فاتورة مشتريات',
    ]);

    $this->actingAs($accountant);

    Livewire::test(PurchaseOrderForm::class)
        ->set('supplier_id', (string) $supplier->id)
        ->set('document_date', '2025-06-15')
        ->set('lines.0.title', 'بند تجريبي')
        ->set('lines.0.unit_price', '100')
        ->set('lines.0.quantity', '1')
        ->call('save')
        ->assertHasNoErrors();

    $po = PurchaseOrder::where('supplier_id', $supplier->id)->first();
    expect($po)->not->toBeNull()
        ->and((float) $po->total_amount)->toBe(100.0)
        ->and($po->lines)->toHaveCount(1);
});

test('viewer cannot access purchase order create page', function () {
    $this->actingAs($this->viewer);
    $this->get(route('purchase-orders.create'))->assertForbidden();
});

test('accountant can open purchase order create page', function () {
    $accountant = User::factory()->create(['role' => 'accountant', 'is_active' => true]);
    $this->actingAs($accountant);
    $this->get(route('purchase-orders.create'))->assertOk();
});

test('supplier payment show loads when payment exists', function () {
    $supplier = Supplier::create([
        'legacy_number' => 'sp-t-'.uniqid(),
        'business_name' => 'مورد دفعات',
    ]);

    $pay = SupplierPayment::create([
        'supplier_id' => $supplier->id,
        'amount' => 50,
        'currency_code' => 'ILS',
        'paid_at' => '2025-06-02 10:00:00',
        'method' => 'cash',
        'bank_reference' => null,
        'notes' => null,
        'recorded_by_user_id' => null,
    ]);

    $this->actingAs($this->viewer);
    $this->get(route('supplier-payments.show', $pay))->assertOk();
});
