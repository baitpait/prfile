<?php

use App\Models\Client;
use App\Models\ClientPayment;
use App\Models\Invoice;
use App\Models\User;
use App\Services\ClientStatementService;

beforeEach(function () {
    $this->manager = User::factory()->create(['role' => 'manager',   'is_active' => true]);
    $this->accountant = User::factory()->create(['role' => 'accountant', 'is_active' => true]);
    $this->viewer = User::factory()->create(['role' => 'viewer',    'is_active' => true]);
});

// معيار القبول الأول: رصيدان منفصلان صحيحان لعملتين مختلفتين
test('statement shows two separate currency balances', function () {
    $client = Client::factory()->create();

    Invoice::factory()->create([
        'client_id' => $client->id,
        'currency_code' => 'ILS',
        'total_amount' => 1000,
        'status' => 'issued',
        'document_date' => '2025-01-01',
    ]);

    Invoice::factory()->create([
        'client_id' => $client->id,
        'currency_code' => 'USD',
        'total_amount' => 500,
        'status' => 'issued',
        'document_date' => '2025-01-02',
    ]);

    ClientPayment::factory()->create([
        'client_id' => $client->id,
        'currency_code' => 'ILS',
        'amount' => 300,
        'paid_at' => '2025-02-01 00:00:00',
    ]);

    ClientPayment::factory()->create([
        'client_id' => $client->id,
        'currency_code' => 'USD',
        'amount' => 200,
        'paid_at' => '2025-02-01 00:00:00',
    ]);

    $service = new ClientStatementService;
    $statement = $service->forClient($client);

    expect($statement)->toHaveKeys(['ILS', 'USD']);

    expect((float) $statement['ILS']['balance'])->toBe(700.0);
    expect((float) $statement['USD']['balance'])->toBe(300.0);
});

// يجب ألا تختلط أرصدة العملتين
test('currencies never mix', function () {
    $client = Client::factory()->create();

    Invoice::factory()->create([
        'client_id' => $client->id,
        'currency_code' => 'ILS',
        'total_amount' => 5000,
        'status' => 'issued',
        'document_date' => now(),
    ]);

    ClientPayment::factory()->create([
        'client_id' => $client->id,
        'currency_code' => 'USD',
        'amount' => 100,
        'paid_at' => now(),
    ]);

    $statement = (new ClientStatementService)->forClient($client);

    // الرصيد بالشيكل = 5000 (لا يتأثر بدفعة الدولار)
    expect((float) $statement['ILS']['balance'])->toBe(5000.0);
    // الرصيد بالدولار = -100 (دفعة بدون فاتورة = دائن)
    expect((float) $statement['USD']['balance'])->toBe(-100.0);
});

// مستخدم viewer لا يستطيع تعديل دفعة
test('viewer cannot update a payment', function () {
    $client = Client::factory()->create();
    $payment = ClientPayment::factory()->create(['client_id' => $client->id]);

    $this->actingAs($this->viewer);
    $this->assertFalse($this->viewer->can('update', $payment));
});

// مستخدم accountant يستطيع تعديل دفعة
test('accountant can update a payment', function () {
    $client = Client::factory()->create();
    $payment = ClientPayment::factory()->create(['client_id' => $client->id]);

    $this->actingAs($this->accountant);
    $this->assertTrue($this->accountant->can('update', $payment));
});

// مستخدم manager يستطيع حذف دفعة
test('manager can delete a payment', function () {
    $client = Client::factory()->create();
    $payment = ClientPayment::factory()->create(['client_id' => $client->id]);

    $this->actingAs($this->manager);
    $this->assertTrue($this->manager->can('delete', $payment));
});

// CSV يعيد نفس الأرقام الظاهرة
test('csv export rows match statement totals', function () {
    $client = Client::factory()->create();

    Invoice::factory()->create([
        'client_id' => $client->id,
        'currency_code' => 'ILS',
        'total_amount' => 2500,
        'status' => 'issued',
        'document_date' => '2025-03-01',
    ]);

    ClientPayment::factory()->create([
        'client_id' => $client->id,
        'currency_code' => 'ILS',
        'amount' => 1000,
        'paid_at' => '2025-03-15 00:00:00',
    ]);

    $service = new ClientStatementService;
    $statement = $service->forClient($client);
    $rows = $service->toCsvRows($statement);

    // الصف الأول هو الترويسة
    expect($rows[0])->toContain('العملة');

    $invoiceRow = $rows[1];
    expect($invoiceRow[0])->toBe('ILS');
    expect($invoiceRow[1])->toBe('فاتورة');
    expect($invoiceRow[4])->toBe('2,500.00');

    $paymentRow = $rows[2];
    expect($paymentRow[1])->toBe('دفعة');
    expect($paymentRow[4])->toBe('1,000.00');
});

test('client statement page loads for viewer', function () {
    $client = Client::factory()->create();

    $this->actingAs($this->viewer);
    $this->get(route('clients.statement', $client))->assertOk();
});

test('viewer cannot download client statement pdf', function () {
    $client = Client::factory()->create();

    Invoice::factory()->create([
        'client_id' => $client->id,
        'currency_code' => 'ILS',
        'total_amount' => 100,
        'status' => 'issued',
        'document_date' => '2025-04-01',
    ]);

    $this->actingAs($this->viewer);
    $this->get(route('clients.statement.pdf', $client))->assertForbidden();
});

test('accountant can download client statement pdf', function () {
    $client = Client::factory()->create();

    Invoice::factory()->create([
        'client_id' => $client->id,
        'currency_code' => 'ILS',
        'total_amount' => 100,
        'status' => 'issued',
        'document_date' => '2025-04-01',
    ]);

    $this->actingAs($this->accountant);
    $this->get(route('clients.statement.pdf', $client))->assertOk();
});

// الحذف المنطقي لا يظهر في الكشف
test('soft deleted payment is excluded from statement', function () {
    $client = Client::factory()->create();

    Invoice::factory()->create([
        'client_id' => $client->id,
        'currency_code' => 'ILS',
        'total_amount' => 1000,
        'status' => 'issued',
        'document_date' => now(),
    ]);

    $payment = ClientPayment::factory()->create([
        'client_id' => $client->id,
        'currency_code' => 'ILS',
        'amount' => 400,
        'paid_at' => now(),
    ]);

    $payment->delete();

    $statement = (new ClientStatementService)->forClient($client);

    expect((float) $statement['ILS']['total_paid'])->toBe(0.0);
    expect((float) $statement['ILS']['balance'])->toBe(1000.0);
});
