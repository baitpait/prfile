<?php

use App\Models\ClientPayment;
use App\Models\Invoice;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\SupplierPayment;
use App\Models\User;
use App\Services\Documents\PrintViewPdfRenderer;

beforeEach(function () {
    if (! app(PrintViewPdfRenderer::class)->isAvailable()) {
        $this->markTestSkipped('Browsershot / Puppeteer Chrome is not available in this environment.');
    }
});

test('invoice pdf matches print template via browsershot', function () {
    $user = User::factory()->create(['role' => 'viewer', 'is_active' => true]);
    $invoice = Invoice::factory()->create();

    $this->actingAs($user)
        ->get(route('invoices.pdf', $invoice))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');
});

test('client payment pdf is generated from voucher print template', function () {
    $user = User::factory()->create(['role' => 'viewer', 'is_active' => true]);
    $payment = ClientPayment::factory()->create();

    $this->actingAs($user)
        ->get(route('payments.pdf', $payment))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');
});

test('purchase order pdf is generated from print template', function () {
    $user = User::factory()->create(['role' => 'viewer', 'is_active' => true]);

    $supplier = Supplier::create([
        'legacy_number' => 'pdf-po-'.uniqid(),
        'business_name' => 'مورد PDF',
    ]);

    $po = PurchaseOrder::create([
        'supplier_id' => $supplier->id,
        'document_date' => '2025-06-01',
        'currency_code' => 'ILS',
        'discount_amount' => 0,
        'total_amount' => 500,
        'status' => 'issued',
    ]);

    $this->actingAs($user)
        ->get(route('purchase-orders.pdf', $po))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');
});

test('supplier payment pdf is generated from voucher print template', function () {
    $user = User::factory()->create(['role' => 'viewer', 'is_active' => true]);

    $supplier = Supplier::create([
        'legacy_number' => 'pdf-sp-'.uniqid(),
        'business_name' => 'مورد دفعة',
    ]);

    $payment = SupplierPayment::create([
        'supplier_id' => $supplier->id,
        'amount' => 100,
        'currency_code' => 'ILS',
        'paid_at' => '2025-06-01 10:00:00',
        'method' => 'cash',
    ]);

    $this->actingAs($user)
        ->get(route('supplier-payments.pdf', $payment))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');
});

test('guest cannot download document pdf', function () {
    $invoice = Invoice::factory()->create();

    $this->get(route('invoices.pdf', $invoice))->assertRedirect();
});
