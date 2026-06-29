<?php

use App\Livewire\InvoiceForm;
use App\Models\Client;
use App\Models\ClientPayment;
use App\Models\Invoice;
use App\Models\User;
use App\Services\InvoicePaymentAllocationService;
use Livewire\Livewire;

test('invoice create page shows payment collection when document is issued', function () {
    $user = User::factory()->create(['is_active' => true, 'role' => 'accountant']);
    $client = Client::factory()->create();

    Livewire::actingAs($user)
        ->test(InvoiceForm::class)
        ->set('client_id', (string) $client->id)
        ->set('status', 'issued')
        ->assertSee('حالة الدفع');
});

test('creating issued unpaid invoice does not create client payment', function () {
    $user = User::factory()->create(['is_active' => true, 'role' => 'accountant']);
    $client = Client::factory()->create();

    Livewire::actingAs($user)
        ->test(InvoiceForm::class)
        ->set('client_id', (string) $client->id)
        ->set('status', 'issued')
        ->set('payment_collection', 'unpaid')
        ->set('document_date', now()->format('Y-m-d'))
        ->set('currency_code', 'ILS')
        ->set('lines.0.title', 'خدمة تصميم')
        ->set('lines.0.unit_price', '500')
        ->set('lines.0.quantity', '1')
        ->call('save')
        ->assertRedirect(route('invoices.index'));

    expect(Invoice::query()->where('client_id', $client->id)->count())->toBe(1);
    expect(ClientPayment::query()->where('client_id', $client->id)->count())->toBe(0);

    $invoice = Invoice::query()->where('client_id', $client->id)->first();
    $status = (new InvoicePaymentAllocationService)->forInvoice($invoice);
    expect($status['status'])->toBe(InvoicePaymentAllocationService::UNPAID);
});

test('creating issued paid invoice creates full client payment', function () {
    $user = User::factory()->create(['is_active' => true, 'role' => 'accountant']);
    $client = Client::factory()->create();

    Livewire::actingAs($user)
        ->test(InvoiceForm::class)
        ->set('client_id', (string) $client->id)
        ->set('status', 'issued')
        ->set('payment_collection', 'paid')
        ->set('payment_method', 'cash')
        ->set('paid_at', now()->format('Y-m-d'))
        ->set('document_date', now()->format('Y-m-d'))
        ->set('currency_code', 'ILS')
        ->set('lines.0.title', 'خدمة طباعة')
        ->set('lines.0.unit_price', '800')
        ->set('lines.0.quantity', '1')
        ->call('save')
        ->assertRedirect(route('invoices.index'));

    $payment = ClientPayment::query()->where('client_id', $client->id)->sole();
    expect((float) $payment->amount)->toBe(800.0);

    $invoice = Invoice::query()->where('client_id', $client->id)->first();
    $status = (new InvoicePaymentAllocationService)->forInvoice($invoice);
    expect($status['status'])->toBe(InvoicePaymentAllocationService::PAID);
});

test('creating issued partial invoice creates partial client payment', function () {
    $user = User::factory()->create(['is_active' => true, 'role' => 'accountant']);
    $client = Client::factory()->create();

    Livewire::actingAs($user)
        ->test(InvoiceForm::class)
        ->set('client_id', (string) $client->id)
        ->set('status', 'issued')
        ->set('payment_collection', 'partial')
        ->set('payment_amount', '300')
        ->set('payment_method', 'transfer')
        ->set('paid_at', now()->format('Y-m-d'))
        ->set('document_date', now()->format('Y-m-d'))
        ->set('currency_code', 'ILS')
        ->set('lines.0.title', 'بكج إعلاني')
        ->set('lines.0.unit_price', '1000')
        ->set('lines.0.quantity', '1')
        ->call('save')
        ->assertRedirect(route('invoices.index'));

    $payment = ClientPayment::query()->where('client_id', $client->id)->sole();
    expect((float) $payment->amount)->toBe(300.0);

    $invoice = Invoice::query()->where('client_id', $client->id)->first();
    $status = (new InvoicePaymentAllocationService)->forInvoice($invoice);
    expect($status['status'])->toBe(InvoicePaymentAllocationService::PARTIAL);
});

test('fifo allocation marks older invoices paid before newer ones', function () {
    $client = Client::factory()->create();

    $older = Invoice::factory()->create([
        'client_id' => $client->id,
        'status' => 'issued',
        'currency_code' => 'ILS',
        'total_amount' => 500,
        'document_date' => now()->subDays(10),
    ]);

    $newer = Invoice::factory()->create([
        'client_id' => $client->id,
        'status' => 'issued',
        'currency_code' => 'ILS',
        'total_amount' => 500,
        'document_date' => now()->subDays(2),
    ]);

    ClientPayment::factory()->create([
        'client_id' => $client->id,
        'amount' => 500,
        'currency_code' => 'ILS',
        'method' => 'cash',
    ]);

    $service = new InvoicePaymentAllocationService;
    expect($service->forInvoice($older)['status'])->toBe(InvoicePaymentAllocationService::PAID);
    expect($service->forInvoice($newer)['status'])->toBe(InvoicePaymentAllocationService::UNPAID);
});
