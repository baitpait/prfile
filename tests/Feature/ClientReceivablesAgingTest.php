<?php

use App\Livewire\ClientReceivablesAgingReport;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\User;
use App\Services\ClientReceivablesAgingService;
use Livewire\Livewire;

beforeEach(function () {
    $this->viewer = User::factory()->create(['role' => 'viewer', 'is_active' => true]);
    $this->accountant = User::factory()->create(['role' => 'accountant', 'is_active' => true]);
});

test('receivables aging page loads for active user', function () {
    $this->actingAs($this->viewer);
    $this->get(route('reports.client-receivables-aging'))->assertOk();
});

test('aging service lists invoice when client has receivable balance', function () {
    $client = Client::factory()->create();

    Invoice::factory()->create([
        'client_id' => $client->id,
        'currency_code' => 'ILS',
        'total_amount' => 800,
        'status' => 'issued',
        'document_date' => '2025-01-10',
        'due_date' => '2025-02-01',
    ]);

    $rows = (new ClientReceivablesAgingService)->rows('ILS');

    expect($rows)->not->toBeEmpty();
    $first = $rows->first();
    expect($first['client_id'])->toBe($client->id);
    expect($first['currency_code'])->toBe('ILS');
    expect((float) $first['total_amount'])->toBe(800.0);
});

test('viewer cannot export receivables aging csv via livewire', function () {
    $this->actingAs($this->viewer);

    Livewire::test(ClientReceivablesAgingReport::class)
        ->call('exportCsv')
        ->assertForbidden();
});

test('accountant can export receivables aging csv via livewire', function () {
    $this->actingAs($this->accountant);

    Livewire::test(ClientReceivablesAgingReport::class)
        ->call('exportCsv')
        ->assertOk();
});
