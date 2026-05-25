<?php

use App\Livewire\ClientReceivablesAgingReport;
use App\Models\Client;
use App\Models\ClientPayment;
use App\Models\Invoice;
use App\Models\User;
use App\Services\ClientReceivablesAgingFilters;
use App\Services\ClientReceivablesAgingService;
use Carbon\Carbon;
use Livewire\Livewire;

beforeEach(function () {
    $this->viewer = User::factory()->create(['role' => 'viewer', 'is_active' => true]);
    $this->accountant = User::factory()->create(['role' => 'accountant', 'is_active' => true]);
});

function ilsFilters(
    ?string $agingBucket = null,
    ?int $daysMin = null,
    ?int $daysMax = null,
    ?float $minBalance = null,
    ?string $search = null,
): ClientReceivablesAgingFilters {
    return new ClientReceivablesAgingFilters(
        currency: 'ILS',
        agingBucket: $agingBucket,
        daysMin: $daysMin,
        daysMax: $daysMax,
        minBalance: $minBalance,
        search: $search,
    );
}

test('receivables aging page loads for active user', function () {
    $this->actingAs($this->viewer);
    $this->get(route('reports.client-receivables-aging'))->assertOk();
});

test('aging service lists client balance when receivable exists', function () {
    $client = Client::factory()->create(['phone_primary' => '+972-50-111-2222']);

    Invoice::factory()->create([
        'client_id' => $client->id,
        'currency_code' => 'ILS',
        'total_amount' => 800,
        'status' => 'issued',
        'document_date' => '2025-01-10',
        'due_date' => '2025-02-01',
    ]);

    $rows = (new ClientReceivablesAgingService)->rows(ilsFilters());

    expect($rows)->toHaveCount(1);
    $first = $rows->first();
    expect($first['client_id'])->toBe($client->id);
    expect($first['currency_code'])->toBe('ILS');
    expect((float) $first['balance'])->toBe(800.0);
    expect($first['phone'])->toBe('+972-50-111-2222');
    expect($first['days_from_first_unpaid'])->toBeGreaterThan(0);
});

test('fifo uses oldest open invoice after partial payment', function () {
    Carbon::setTestNow('2026-05-25');

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
        'currency_code' => 'ILS',
        'total_amount' => 500,
        'status' => 'issued',
        'document_date' => '2025-06-01',
    ]);

    ClientPayment::factory()->create([
        'client_id' => $client->id,
        'currency_code' => 'ILS',
        'amount' => 1000,
        'paid_at' => '2025-07-01',
    ]);

    $row = (new ClientReceivablesAgingService)->rows(ilsFilters())->first();

    expect((float) $row['balance'])->toBe(500.0);
    expect($row['first_unpaid_document_date'])->toBe('2025-06-01');
    expect($row['days_from_first_unpaid'])->toBe((int) Carbon::parse('2025-06-01')->diffInDays(Carbon::parse('2026-05-25')));

    Carbon::setTestNow();
});

test('rows are sorted by days from first unpaid descending', function () {
    Carbon::setTestNow('2026-05-25');

    $clientOld = Client::factory()->create();
    $clientNew = Client::factory()->create();

    Invoice::factory()->create([
        'client_id' => $clientOld->id,
        'currency_code' => 'ILS',
        'total_amount' => 100,
        'status' => 'issued',
        'document_date' => '2024-01-01',
    ]);

    Invoice::factory()->create([
        'client_id' => $clientNew->id,
        'currency_code' => 'ILS',
        'total_amount' => 200,
        'status' => 'issued',
        'document_date' => '2026-05-01',
    ]);

    $rows = (new ClientReceivablesAgingService)->rows(ilsFilters());

    expect($rows)->toHaveCount(2);
    expect($rows[0]['client_id'])->toBe($clientOld->id);
    expect($rows[0]['days_from_first_unpaid'])->toBeGreaterThan($rows[1]['days_from_first_unpaid']);

    Carbon::setTestNow();
});

test('summary provides cumulative aging totals', function () {
    Carbon::setTestNow('2026-05-25');

    $clientA = Client::factory()->create();
    $clientB = Client::factory()->create();

    Invoice::factory()->create([
        'client_id' => $clientA->id,
        'currency_code' => 'ILS',
        'total_amount' => 300,
        'status' => 'issued',
        'document_date' => '2026-05-01',
    ]);

    Invoice::factory()->create([
        'client_id' => $clientB->id,
        'currency_code' => 'ILS',
        'total_amount' => 700,
        'status' => 'issued',
        'document_date' => '2025-01-01',
    ]);

    $summary = (new ClientReceivablesAgingService)->summary(ilsFilters());

    expect($summary['client_count'])->toBe(2);
    expect((float) $summary['total_balance'])->toBe(1000.0);
    expect((float) $summary['cumulative']['all'])->toBe(1000.0);
    expect((float) $summary['buckets']['0_30'])->toBe(300.0);
    expect((float) $summary['buckets']['91_plus'])->toBe(700.0);

    Carbon::setTestNow();
});

test('aging bucket filter limits rows to delay range', function () {
    Carbon::setTestNow('2026-05-25');

    $recent = Client::factory()->create(['business_name' => 'حديث']);
    $old = Client::factory()->create(['business_name' => 'قديم']);

    Invoice::factory()->create([
        'client_id' => $recent->id,
        'currency_code' => 'ILS',
        'total_amount' => 100,
        'status' => 'issued',
        'document_date' => '2026-05-01',
    ]);

    Invoice::factory()->create([
        'client_id' => $old->id,
        'currency_code' => 'ILS',
        'total_amount' => 500,
        'status' => 'issued',
        'document_date' => '2024-01-01',
    ]);

    $rows = (new ClientReceivablesAgingService)->rows(ilsFilters(agingBucket: '91_plus'));

    expect($rows)->toHaveCount(1);
    expect($rows->first()['client_id'])->toBe($old->id);

    Carbon::setTestNow();
});

test('min balance and search filters apply', function () {
    $alpha = Client::factory()->create([
        'business_name' => 'شركة ألفا',
        'phone_primary' => '+972-50-100-2000',
    ]);
    $beta = Client::factory()->create(['business_name' => 'شركة بيتا']);

    Invoice::factory()->create([
        'client_id' => $alpha->id,
        'currency_code' => 'ILS',
        'total_amount' => 50,
        'status' => 'issued',
    ]);

    Invoice::factory()->create([
        'client_id' => $beta->id,
        'currency_code' => 'ILS',
        'total_amount' => 500,
        'status' => 'issued',
    ]);

    $byBalance = (new ClientReceivablesAgingService)->rows(ilsFilters(minBalance: 100.0));
    expect($byBalance)->toHaveCount(1);
    expect($byBalance->first()['client_id'])->toBe($beta->id);

    $bySearch = (new ClientReceivablesAgingService)->rows(ilsFilters(search: 'ألفا'));
    expect($bySearch)->toHaveCount(1);
    expect($bySearch->first()['client_id'])->toBe($alpha->id);

    $byPhone = (new ClientReceivablesAgingService)->rows(ilsFilters(search: '100-2000'));
    expect($byPhone)->toHaveCount(1);
    expect($byPhone->first()['client_id'])->toBe($alpha->id);
});

test('livewire clear filters resets url state', function () {
    $this->actingAs($this->viewer);

    Livewire::test(ClientReceivablesAgingReport::class)
        ->set('currency', 'ILS')
        ->set('agingBucket', '91_plus')
        ->set('search', 'test')
        ->call('clearFilters')
        ->assertSet('currency', '')
        ->assertSet('agingBucket', '')
        ->assertSet('search', '');
});

test('viewer cannot download receivables aging pdf', function () {
    $this->actingAs($this->viewer);

    $this->get(route('reports.client-receivables-aging.pdf'))->assertForbidden();
});

test('accountant can download receivables aging pdf', function () {
    $client = Client::factory()->create();

    Invoice::factory()->create([
        'client_id' => $client->id,
        'currency_code' => 'ILS',
        'total_amount' => 400,
        'status' => 'issued',
    ]);

    $this->actingAs($this->accountant);

    $this->get(route('reports.client-receivables-aging.pdf', ['currency' => 'ILS']))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');
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
