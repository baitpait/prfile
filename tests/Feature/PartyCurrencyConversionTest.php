<?php

use App\Livewire\PartyCurrencyConverter;
use App\Models\Client;
use App\Models\ClientBalanceAdjustment;
use App\Models\ClientPayment;
use App\Models\Invoice;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\SupplierBalanceAdjustment;
use App\Models\SupplierPayment;
use App\Models\User;
use App\Services\PartyCurrencyConversionService;
use Livewire\Livewire;

beforeEach(function () {
    $this->manager = User::factory()->create(['role' => 'manager', 'is_active' => true]);
    $this->accountant = User::factory()->create(['role' => 'accountant', 'is_active' => true]);
});

test('client currency conversion relabels rows without changing amounts', function () {
    $client = Client::factory()->create();

    $draft = Invoice::factory()->create([
        'client_id' => $client->id,
        'currency_code' => 'ILS',
        'total_amount' => 1500,
        'status' => 'draft',
        'document_date' => '2025-03-01',
    ]);

    $issued = Invoice::factory()->create([
        'client_id' => $client->id,
        'currency_code' => 'ILS',
        'total_amount' => 2500,
        'status' => 'issued',
        'document_date' => '2025-03-02',
    ]);

    $payment = ClientPayment::factory()->create([
        'client_id' => $client->id,
        'currency_code' => 'ILS',
        'amount' => 500,
        'paid_at' => '2025-03-10 00:00:00',
    ]);

    $adjustment = ClientBalanceAdjustment::factory()->create([
        'client_id' => $client->id,
        'currency_code' => 'ILS',
        'amount' => 100,
    ]);

    $this->actingAs($this->manager);

    $result = (new PartyCurrencyConversionService)->applyClient($client, 'ILS', 'USD', $this->manager->id);

    expect($result['total'])->toBe(4);
    expect($draft->fresh()->currency_code)->toBe('USD');
    expect((float) $draft->fresh()->total_amount)->toBe(1500.0);
    expect($issued->fresh()->currency_code)->toBe('USD');
    expect((float) $issued->fresh()->total_amount)->toBe(2500.0);
    expect($payment->fresh()->currency_code)->toBe('USD');
    expect((float) $payment->fresh()->amount)->toBe(500.0);
    expect($adjustment->fresh()->currency_code)->toBe('USD');
    expect((float) $adjustment->fresh()->amount)->toBe(100.0);
});

test('supplier currency conversion includes draft purchase orders', function () {
    $supplier = Supplier::create([
        'legacy_number' => 'cc-'.uniqid(),
        'business_name' => 'مورد تحويل',
    ]);

    PurchaseOrder::create([
        'supplier_id' => $supplier->id,
        'legacy_po_no' => 'PO-D',
        'document_date' => '2025-04-01',
        'due_date' => null,
        'currency_code' => 'ILS',
        'discount_amount' => 0,
        'total_amount' => 800,
        'notes' => null,
        'status' => 'draft',
        'recorded_by_user_id' => null,
    ]);

    SupplierPayment::create([
        'supplier_id' => $supplier->id,
        'amount' => 200,
        'currency_code' => 'ILS',
        'paid_at' => '2025-04-05 00:00:00',
        'method' => null,
        'bank_reference' => null,
        'notes' => null,
        'recorded_by_user_id' => null,
    ]);

    SupplierBalanceAdjustment::create([
        'supplier_id' => $supplier->id,
        'amount' => 50,
        'currency_code' => 'ILS',
        'adjustment_date' => '2025-04-06',
        'type' => SupplierBalanceAdjustment::TYPE_SETTLEMENT_DISCOUNT,
        'reason' => 'خصم',
        'recorded_by_user_id' => null,
    ]);

    $this->actingAs($this->manager);

    $result = (new PartyCurrencyConversionService)->applySupplier($supplier, 'ILS', 'USD', $this->manager->id);

    expect($result['total'])->toBe(3);
    expect(PurchaseOrder::where('supplier_id', $supplier->id)->where('currency_code', 'USD')->count())->toBe(1);
    expect(SupplierPayment::where('supplier_id', $supplier->id)->where('currency_code', 'USD')->count())->toBe(1);
    expect(SupplierBalanceAdjustment::where('supplier_id', $supplier->id)->where('currency_code', 'USD')->count())->toBe(1);
    expect((float) PurchaseOrder::where('supplier_id', $supplier->id)->sum('total_amount'))->toBe(800.0);
});

test('accountant cannot access party currency converter', function () {
    $client = Client::factory()->create();

    $this->actingAs($this->accountant);

    Livewire::test(PartyCurrencyConverter::class, [
        'partyType' => 'client',
        'partyId' => $client->id,
    ])->assertForbidden();
});

test('manager can preview and apply conversion via livewire', function () {
    $client = Client::factory()->create();

    Invoice::factory()->create([
        'client_id' => $client->id,
        'currency_code' => 'ILS',
        'total_amount' => 1000,
        'status' => 'issued',
        'document_date' => now(),
    ]);

    $this->actingAs($this->manager);

    Livewire::test(PartyCurrencyConverter::class, [
        'partyType' => 'client',
        'partyId' => $client->id,
    ])
        ->call('openModal')
        ->set('fromCurrency', 'ILS')
        ->set('toCurrency', 'USD')
        ->call('runPreview')
        ->assertSet('preview.total', 1)
        ->call('applyConversion')
        ->assertDispatched('currency-converted');

    expect(Invoice::where('client_id', $client->id)->value('currency_code'))->toBe('USD');
});
