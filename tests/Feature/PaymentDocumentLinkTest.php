<?php

use App\Livewire\PaymentForm;
use App\Livewire\SupplierPaymentForm;
use App\Models\Client;
use App\Models\ClientPayment;
use App\Models\Invoice;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\SupplierPayment;
use App\Models\User;
use App\Services\InvoicePaymentAllocationService;
use App\Services\PurchaseOrderPaymentAllocationService;
use Livewire\Livewire;

test('client payment on invoice locks amount to invoice total and links invoice_id', function () {
    $accountant = User::factory()->create(['role' => 'accountant', 'is_active' => true]);
    $client = Client::factory()->create();
    $invoice = Invoice::factory()->create([
        'client_id' => $client->id,
        'currency_code' => 'ILS',
        'total_amount' => 750,
        'status' => 'issued',
        'document_date' => now()->format('Y-m-d'),
    ]);

    Livewire::actingAs($accountant)
        ->test(PaymentForm::class)
        ->set('client_id', (string) $client->id)
        ->set('allocation_mode', 'invoice')
        ->set('currency_code', 'ILS')
        ->set('invoice_id', (string) $invoice->id)
        ->assertSet('amount', '750.00')
        ->set('paid_at', now()->format('Y-m-d'))
        ->set('payment_method', 'cash')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('payments.index'));

    $payment = ClientPayment::query()->where('client_id', $client->id)->sole();
    expect($payment->invoice_id)->toBe($invoice->id);
    expect((float) $payment->amount)->toBe(750.0);

    $status = (new InvoicePaymentAllocationService)->forInvoice($invoice->fresh());
    expect($status['status'])->toBe(InvoicePaymentAllocationService::PAID);
});

test('client payment rejects amount different from linked invoice total', function () {
    $accountant = User::factory()->create(['role' => 'accountant', 'is_active' => true]);
    $client = Client::factory()->create();
    $invoice = Invoice::factory()->create([
        'client_id' => $client->id,
        'currency_code' => 'ILS',
        'total_amount' => 500,
        'status' => 'issued',
        'document_date' => now()->format('Y-m-d'),
    ]);

    Livewire::actingAs($accountant)
        ->test(PaymentForm::class)
        ->set('client_id', (string) $client->id)
        ->set('allocation_mode', 'invoice')
        ->set('currency_code', 'ILS')
        ->set('invoice_id', (string) $invoice->id)
        ->set('amount', '400')
        ->set('paid_at', now()->format('Y-m-d'))
        ->set('payment_method', 'cash')
        ->call('save')
        ->assertHasErrors(['amount']);

    expect(ClientPayment::query()->where('client_id', $client->id)->count())->toBe(0);
});

test('client payment on account leaves invoice_id null', function () {
    $accountant = User::factory()->create(['role' => 'accountant', 'is_active' => true]);
    $client = Client::factory()->create();

    Livewire::actingAs($accountant)
        ->test(PaymentForm::class)
        ->set('client_id', (string) $client->id)
        ->set('allocation_mode', 'account')
        ->set('amount', '120.50')
        ->set('currency_code', 'ILS')
        ->set('paid_at', now()->format('Y-m-d'))
        ->set('payment_method', 'transfer')
        ->call('save')
        ->assertHasNoErrors();

    $payment = ClientPayment::query()->where('client_id', $client->id)->sole();
    expect($payment->invoice_id)->toBeNull();
    expect((float) $payment->amount)->toBe(120.5);
});

test('linked invoice payment does not consume fifo pool for other invoices', function () {
    $client = Client::factory()->create();
    $older = Invoice::factory()->create([
        'client_id' => $client->id,
        'currency_code' => 'ILS',
        'total_amount' => 1000,
        'status' => 'issued',
        'document_date' => now()->subDays(10)->format('Y-m-d'),
    ]);
    $newer = Invoice::factory()->create([
        'client_id' => $client->id,
        'currency_code' => 'ILS',
        'total_amount' => 400,
        'status' => 'issued',
        'document_date' => now()->format('Y-m-d'),
    ]);

    ClientPayment::query()->create([
        'client_id' => $client->id,
        'invoice_id' => $newer->id,
        'amount' => 400,
        'currency_code' => 'ILS',
        'paid_at' => now(),
        'method' => 'cash',
    ]);

    $service = new InvoicePaymentAllocationService;
    expect($service->forInvoice($newer)['status'])->toBe(InvoicePaymentAllocationService::PAID);
    expect($service->forInvoice($older)['status'])->toBe(InvoicePaymentAllocationService::UNPAID);
});

test('supplier payment on purchase order locks amount and links purchase_order_id', function () {
    $accountant = User::factory()->create(['role' => 'accountant', 'is_active' => true]);
    $supplier = Supplier::factory()->create();
    $po = PurchaseOrder::query()->create([
        'supplier_id' => $supplier->id,
        'currency_code' => 'ILS',
        'total_amount' => 900,
        'discount_amount' => 0,
        'status' => 'issued',
        'document_date' => now()->format('Y-m-d'),
    ]);

    Livewire::actingAs($accountant)
        ->test(SupplierPaymentForm::class)
        ->set('supplier_id', (string) $supplier->id)
        ->set('allocation_mode', 'purchase_order')
        ->set('currency_code', 'ILS')
        ->set('purchase_order_id', (string) $po->id)
        ->assertSet('amount', '900.00')
        ->set('paid_at', now()->format('Y-m-d'))
        ->set('payment_method', 'bank')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('supplier-payments.index'));

    $payment = SupplierPayment::query()->where('supplier_id', $supplier->id)->sole();
    expect($payment->purchase_order_id)->toBe($po->id);
    expect((float) $payment->amount)->toBe(900.0);

    $status = (new PurchaseOrderPaymentAllocationService)->forPurchaseOrder($po->fresh());
    expect($status['status'])->toBe(PurchaseOrderPaymentAllocationService::PAID);
});
