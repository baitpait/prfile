<?php

use App\Models\Client;
use App\Models\Invoice;
use App\Models\User;

test('invoice print shows balance due when client has prior receivable', function () {
    $user = User::factory()->create(['role' => 'viewer', 'is_active' => true]);
    $client = Client::factory()->create();

    Invoice::factory()->create([
        'client_id' => $client->id,
        'currency_code' => 'ILS',
        'total_amount' => 1000,
        'status' => 'issued',
        'document_date' => '2025-01-01',
    ]);

    $current = Invoice::factory()->create([
        'client_id' => $client->id,
        'currency_code' => 'ILS',
        'total_amount' => 500,
        'status' => 'issued',
        'document_date' => '2026-06-01',
    ]);

    $this->actingAs($user)
        ->get(route('invoices.print', $current))
        ->assertOk()
        ->assertSee('المبلغ المستحق', false)
        ->assertSee('1,500.00', false);
});

test('invoice print hides balance due when only current invoice is owed', function () {
    $user = User::factory()->create(['role' => 'viewer', 'is_active' => true]);
    $client = Client::factory()->create();

    $invoice = Invoice::factory()->create([
        'client_id' => $client->id,
        'currency_code' => 'ILS',
        'total_amount' => 500,
        'status' => 'issued',
    ]);

    $this->actingAs($user)
        ->get(route('invoices.print', $invoice))
        ->assertOk()
        ->assertSee('مجموع الفاتورة', false)
        ->assertDontSee('المبلغ المستحق', false);
});
