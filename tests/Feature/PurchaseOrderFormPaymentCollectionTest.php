<?php

use App\Livewire\PurchaseOrderForm;
use App\Models\PurchaseOrder;
use App\Models\ReceivedCheck;
use App\Models\Supplier;
use App\Models\SupplierPayment;
use App\Models\User;
use App\Services\Finance\ReceivedCheckStatus;
use App\Services\PurchaseOrderPaymentAllocationService;
use Livewire\Livewire;

test('purchase order create shows payment collection when document is issued', function () {
    $user = User::factory()->create(['is_active' => true, 'role' => 'accountant']);
    $supplier = Supplier::factory()->create();

    Livewire::actingAs($user)
        ->test(PurchaseOrderForm::class)
        ->set('supplier_id', (string) $supplier->id)
        ->assertSee('حالة الدفع');
});

test('creating issued unpaid purchase order does not create supplier payment', function () {
    $user = User::factory()->create(['is_active' => true, 'role' => 'accountant']);
    $supplier = Supplier::factory()->create();

    Livewire::actingAs($user)
        ->test(PurchaseOrderForm::class)
        ->set('supplier_id', (string) $supplier->id)
        ->set('status', 'issued')
        ->set('payment_collection', 'unpaid')
        ->set('document_date', now()->format('Y-m-d'))
        ->set('currency_code', 'ILS')
        ->set('lines.0.title', 'مواد طباعة')
        ->set('lines.0.unit_price', '500')
        ->set('lines.0.quantity', '1')
        ->call('save')
        ->assertRedirect(route('purchase-orders.index'));

    expect(PurchaseOrder::query()->where('supplier_id', $supplier->id)->count())->toBe(1);
    expect(SupplierPayment::query()->where('supplier_id', $supplier->id)->count())->toBe(0);

    $po = PurchaseOrder::query()->where('supplier_id', $supplier->id)->first();
    $status = (new PurchaseOrderPaymentAllocationService)->forPurchaseOrder($po);
    expect($status['status'])->toBe(PurchaseOrderPaymentAllocationService::UNPAID);
});

test('creating issued paid purchase order creates full supplier payment', function () {
    $user = User::factory()->create(['is_active' => true, 'role' => 'accountant']);
    $supplier = Supplier::factory()->create();

    Livewire::actingAs($user)
        ->test(PurchaseOrderForm::class)
        ->set('supplier_id', (string) $supplier->id)
        ->set('status', 'issued')
        ->set('payment_collection', 'paid')
        ->set('payment_method', 'cash')
        ->set('paid_at', now()->format('Y-m-d'))
        ->set('document_date', now()->format('Y-m-d'))
        ->set('currency_code', 'ILS')
        ->set('lines.0.title', 'حبر')
        ->set('lines.0.unit_price', '800')
        ->set('lines.0.quantity', '1')
        ->call('save')
        ->assertRedirect(route('purchase-orders.index'));

    $payment = SupplierPayment::query()->where('supplier_id', $supplier->id)->sole();
    expect((float) $payment->amount)->toBe(800.0);
    expect($payment->purchase_order_id)->not->toBeNull();

    $po = PurchaseOrder::query()->where('supplier_id', $supplier->id)->first();
    expect($payment->purchase_order_id)->toBe($po->id);
    $status = (new PurchaseOrderPaymentAllocationService)->forPurchaseOrder($po);
    expect($status['status'])->toBe(PurchaseOrderPaymentAllocationService::PAID);
});

test('creating issued partial purchase order creates partial supplier payment', function () {
    $user = User::factory()->create(['is_active' => true, 'role' => 'accountant']);
    $supplier = Supplier::factory()->create();

    Livewire::actingAs($user)
        ->test(PurchaseOrderForm::class)
        ->set('supplier_id', (string) $supplier->id)
        ->set('status', 'issued')
        ->set('payment_collection', 'partial')
        ->set('payment_amount', '300')
        ->set('payment_method', 'transfer')
        ->set('paid_at', now()->format('Y-m-d'))
        ->set('document_date', now()->format('Y-m-d'))
        ->set('currency_code', 'ILS')
        ->set('lines.0.title', 'ورق')
        ->set('lines.0.unit_price', '1000')
        ->set('lines.0.quantity', '1')
        ->call('save')
        ->assertRedirect(route('purchase-orders.index'));

    $payment = SupplierPayment::query()->where('supplier_id', $supplier->id)->sole();
    expect((float) $payment->amount)->toBe(300.0);

    $po = PurchaseOrder::query()->where('supplier_id', $supplier->id)->first();
    $status = (new PurchaseOrderPaymentAllocationService)->forPurchaseOrder($po);
    expect($status['status'])->toBe(PurchaseOrderPaymentAllocationService::PARTIAL);
});

test('creating issued paid purchase order endorses pending check from register', function () {
    $user = User::factory()->create(['is_active' => true, 'role' => 'accountant']);
    $supplier = Supplier::factory()->create();
    $client = \App\Models\Client::factory()->create();
    $check = makePendingCheck($client, 750, 'ILS', ReceivedCheckStatus::PENDING, now()->addDays(10), 'PO-REG-1');

    Livewire::actingAs($user)
        ->test(PurchaseOrderForm::class)
        ->set('supplier_id', (string) $supplier->id)
        ->set('status', 'issued')
        ->set('payment_collection', 'paid')
        ->set('payment_method', 'check')
        ->set('check_source', 'register')
        ->set('paid_at', now()->format('Y-m-d'))
        ->set('document_date', now()->format('Y-m-d'))
        ->set('currency_code', 'ILS')
        ->set('lines.0.title', 'معدات')
        ->set('lines.0.unit_price', '750')
        ->set('lines.0.quantity', '1')
        ->set('received_check_id', (string) $check->id)
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('purchase-orders.index'));

    $check->refresh();
    expect($check->status)->toBe(ReceivedCheckStatus::ENDORSED);
    expect($check->supplier_payment_id)->not->toBeNull();

    $po = PurchaseOrder::query()->where('supplier_id', $supplier->id)->sole();
    $payment = SupplierPayment::query()->find($check->supplier_payment_id);
    expect($payment)->not->toBeNull();
    expect($payment->purchase_order_id)->toBe($po->id);
    expect((float) $payment->amount)->toBe(750.0);
});

test('creating issued paid purchase order with manual check stores bank reference', function () {
    $user = User::factory()->create(['is_active' => true, 'role' => 'accountant']);
    $supplier = Supplier::factory()->create();

    Livewire::actingAs($user)
        ->test(PurchaseOrderForm::class)
        ->set('supplier_id', (string) $supplier->id)
        ->set('status', 'issued')
        ->set('payment_collection', 'paid')
        ->set('payment_method', 'check')
        ->set('check_source', 'manual')
        ->set('bank_reference', 'OUT-PO-88')
        ->set('paid_at', now()->format('Y-m-d'))
        ->set('document_date', now()->format('Y-m-d'))
        ->set('currency_code', 'ILS')
        ->set('lines.0.title', 'لوازم')
        ->set('lines.0.unit_price', '420')
        ->set('lines.0.quantity', '1')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('purchase-orders.index'));

    $payment = SupplierPayment::query()->where('supplier_id', $supplier->id)->sole();
    expect($payment->method)->toBe('check');
    expect($payment->bank_reference)->toBe('OUT-PO-88');
    expect(ReceivedCheck::query()->where('status', ReceivedCheckStatus::ENDORSED)->count())->toBe(0);
});

test('fifo allocation marks older purchase orders paid before newer ones', function () {
    $supplier = Supplier::factory()->create();

    $older = PurchaseOrder::query()->create([
        'supplier_id' => $supplier->id,
        'document_date' => now()->subDays(10)->format('Y-m-d'),
        'currency_code' => 'ILS',
        'total_amount' => 500,
        'discount_amount' => 0,
        'status' => 'issued',
    ]);

    $newer = PurchaseOrder::query()->create([
        'supplier_id' => $supplier->id,
        'document_date' => now()->subDays(2)->format('Y-m-d'),
        'currency_code' => 'ILS',
        'total_amount' => 500,
        'discount_amount' => 0,
        'status' => 'issued',
    ]);

    SupplierPayment::query()->create([
        'supplier_id' => $supplier->id,
        'amount' => 500,
        'currency_code' => 'ILS',
        'paid_at' => now(),
        'method' => 'cash',
    ]);

    $service = new PurchaseOrderPaymentAllocationService;
    expect($service->forPurchaseOrder($older)['status'])->toBe(PurchaseOrderPaymentAllocationService::PAID);
    expect($service->forPurchaseOrder($newer)['status'])->toBe(PurchaseOrderPaymentAllocationService::UNPAID);
});
