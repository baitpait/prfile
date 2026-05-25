<?php

use App\Models\Client;
use App\Models\ClientBalanceAdjustment;
use App\Models\ClientPayment;
use App\Models\Invoice;
use App\Models\User;
use App\Services\ClientStatementService;

test('statement balance subtracts adjustments from invoices minus payments', function () {
    $client = Client::factory()->create();

    Invoice::factory()->create([
        'client_id' => $client->id,
        'currency_code' => 'ILS',
        'total_amount' => 860,
        'status' => 'issued',
        'document_date' => '2025-06-01',
    ]);

    ClientPayment::factory()->create([
        'client_id' => $client->id,
        'currency_code' => 'ILS',
        'amount' => 800,
        'paid_at' => '2025-06-10 00:00:00',
    ]);

    ClientBalanceAdjustment::factory()->create([
        'client_id' => $client->id,
        'currency_code' => 'ILS',
        'amount' => 60,
        'adjustment_date' => '2025-06-10',
        'type' => ClientBalanceAdjustment::TYPE_SETTLEMENT_DISCOUNT,
        'reason' => 'إعفاء متبقي',
    ]);

    $section = (new ClientStatementService)->forClient($client)['ILS'];

    expect((float) $section['total_invoiced'])->toBe(860.0);
    expect((float) $section['total_paid'])->toBe(800.0);
    expect((float) $section['total_adjusted'])->toBe(60.0);
    expect((float) $section['balance'])->toBe(0.0);
});

test('accountant can open client adjustment create form', function () {
    $client = Client::factory()->create();
    $accountant = User::factory()->create(['role' => 'accountant', 'is_active' => true]);

    $this->actingAs($accountant)
        ->get(route('clients.adjustments.create', $client))
        ->assertOk()
        ->assertSee('تسوية على الذمة');
});

test('statement page shows adjustment in timeline', function () {
    $client = Client::factory()->create();
    $accountant = User::factory()->create(['role' => 'accountant', 'is_active' => true]);

    ClientBalanceAdjustment::factory()->create([
        'client_id' => $client->id,
        'currency_code' => 'ILS',
        'amount' => 60,
        'adjustment_date' => now(),
    ]);

    $this->actingAs($accountant)
        ->get(route('clients.statement', $client))
        ->assertOk()
        ->assertSee('إجمالي التسويات')
        ->assertSee('تسوية');
});
