<?php

use App\Models\Client;
use App\Models\ClientPayment;
use App\Models\Invoice;
use App\Models\InvoiceLine;
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

// CSV يعيد نفس الأرقام والترتيب الزمني مع ملخص الفترة
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

    expect($rows[0])->toContain('العملة');
    expect($rows[1][3])->toBe('+2500.00');
    expect($rows[2][3])->toBe('-1000.00');
    expect($rows[4][3])->toBe('1000.00');
    expect($rows[5][3])->toBe('0.00');
    expect($rows[6][2])->toContain('الرصيد المستحق');
    expect($rows[6][3])->toBe('1500.00');
});

test('client statement page loads for viewer', function () {
    $client = Client::factory()->create();

    $this->actingAs($this->viewer);
    $this->get(route('clients.statement', $client))->assertOk();
});

test('client statement page shows invoice line details and actions', function () {
    $client = Client::factory()->create();

    $invoice = Invoice::factory()->create([
        'client_id' => $client->id,
        'currency_code' => 'ILS',
        'total_amount' => 1600,
        'status' => 'issued',
        'document_date' => '2021-11-27',
        'legacy_invoice_no' => 'ERP-SALE-3',
    ]);

    InvoiceLine::query()->create([
        'invoice_id' => $invoice->id,
        'line_order' => 1,
        'title' => 'حملة انتخابية',
        'unit_price' => 1600,
        'quantity' => 1,
        'line_total' => 1600,
    ]);

    $this->actingAs($this->accountant);
    $this->get(route('clients.statement', $client))
        ->assertOk()
        ->assertSee('إجمالي الفواتير')
        ->assertSee('إجمالي الدفعات')
        ->assertSee('الرصيد المستحق')
        ->assertSee('حركة الحساب')
        ->assertSee('ERP-SALE-3')
        ->assertSee('حملة انتخابية')
        ->assertSee('+1,600.00')
        ->assertSee('المبلغ (ILS)');
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
