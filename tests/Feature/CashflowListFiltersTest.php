<?php

use App\Livewire\ExpenseList;
use App\Livewire\PaymentList;
use App\Livewire\SupplierPaymentList;
use App\Models\Client;
use App\Models\ClientPayment;
use App\Models\Expense;
use App\Models\Supplier;
use App\Models\SupplierPayment;
use App\Models\User;
use Livewire\Livewire;

test('client payment list filters by client search text', function () {
    $user = User::factory()->create(['role' => 'accountant', 'is_active' => true]);

    $clientA = Client::factory()->create(['business_name' => 'شركة ألفا للدفع']);
    $clientB = Client::factory()->create(['business_name' => 'شركة بيتا للدفع']);

    ClientPayment::factory()->create([
        'client_id' => $clientA->id,
        'bank_reference' => 'PAY-ALPHA',
    ]);

    ClientPayment::factory()->create([
        'client_id' => $clientB->id,
        'bank_reference' => 'PAY-BETA',
    ]);

    Livewire::actingAs($user)
        ->test(PaymentList::class)
        ->set('clientSearchDraft', 'ألفا')
        ->call('applyListFilters')
        ->assertSee('PAY-ALPHA')
        ->assertDontSee('PAY-BETA');
});

test('client payment list filters by client and method', function () {
    $user = User::factory()->create(['role' => 'accountant', 'is_active' => true]);

    $clientA = Client::factory()->create(['business_name' => 'عميل دفعات أ']);
    $clientB = Client::factory()->create(['business_name' => 'عميل دفعات ب']);

    ClientPayment::factory()->create([
        'client_id' => $clientA->id,
        'amount' => 100,
        'currency_code' => 'ILS',
        'paid_at' => '2025-04-01',
        'method' => 'cash',
        'bank_reference' => 'REF-A',
    ]);

    ClientPayment::factory()->create([
        'client_id' => $clientB->id,
        'amount' => 200,
        'currency_code' => 'ILS',
        'paid_at' => '2025-04-02',
        'method' => 'bank',
        'bank_reference' => 'REF-B',
    ]);

    Livewire::actingAs($user)
        ->test(PaymentList::class)
        ->set('filterClientId', (string) $clientA->id)
        ->assertSee('REF-A')
        ->assertDontSee('REF-B')
        ->set('filterClientId', '')
        ->set('filterMethod', 'bank')
        ->assertSee('REF-B')
        ->assertDontSee('REF-A');
});

test('supplier payment list filters by currency and date', function () {
    $user = User::factory()->create(['role' => 'accountant', 'is_active' => true]);
    $supplier = Supplier::factory()->create();

    SupplierPayment::create([
        'supplier_id' => $supplier->id,
        'amount' => 50,
        'currency_code' => 'USD',
        'paid_at' => '2025-05-10',
        'method' => 'transfer',
        'bank_reference' => 'SP-USD',
        'notes' => null,
        'recorded_by_user_id' => null,
    ]);

    SupplierPayment::create([
        'supplier_id' => $supplier->id,
        'amount' => 60,
        'currency_code' => 'ILS',
        'paid_at' => '2025-05-20',
        'method' => 'cash',
        'bank_reference' => 'SP-ILS',
        'notes' => null,
        'recorded_by_user_id' => null,
    ]);

    Livewire::actingAs($user)
        ->test(SupplierPaymentList::class)
        ->set('filterCurrency', 'USD')
        ->assertSee('SP-USD')
        ->assertDontSee('SP-ILS')
        ->set('filterCurrency', '')
        ->set('filterDateFrom', '2025-05-15')
        ->assertSee('SP-ILS')
        ->assertDontSee('SP-USD');
});

test('expense list filters by currency and description search', function () {
    $user = User::factory()->create(['role' => 'accountant', 'is_active' => true]);

    Expense::create([
        'description' => 'إيجار مكتب',
        'amount' => 1000,
        'currency_code' => 'ILS',
        'expense_date' => '2025-06-01',
        'notes' => null,
        'recorded_by_user_id' => null,
    ]);

    Expense::create([
        'description' => 'اشتراك برامج',
        'amount' => 50,
        'currency_code' => 'USD',
        'expense_date' => '2025-06-02',
        'notes' => null,
        'recorded_by_user_id' => null,
    ]);

    Livewire::actingAs($user)
        ->test(ExpenseList::class)
        ->set('search', 'إيجار')
        ->assertSee('إيجار مكتب')
        ->assertDontSee('اشتراك برامج')
        ->set('search', '')
        ->set('filterCurrency', 'USD')
        ->assertSee('اشتراك برامج')
        ->assertDontSee('إيجار مكتب');
});
