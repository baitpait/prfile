<?php

use App\Livewire\SupplierPaymentForm;
use App\Models\Supplier;
use App\Models\SupplierPayment;
use App\Models\User;
use App\Services\Finance\PaymentMethod;
use Livewire\Livewire;

test('payment method normalizes legacy arabic and erp placeholders', function () {
    expect(PaymentMethod::normalize('نقداً'))->toBe('cash')
        ->and(PaymentMethod::normalize('تحويل بنكي'))->toBe('transfer')
        ->and(PaymentMethod::normalize('طريقة #3'))->toBe('check')
        ->and(PaymentMethod::normalize('cash'))->toBe('cash')
        ->and(PaymentMethod::normalize(null))->toBe('cash');
});

test('supplier payment form can save when legacy method is stored in database', function () {
    $accountant = User::factory()->create(['role' => 'accountant', 'is_active' => true]);

    $supplier = Supplier::create([
        'legacy_number' => 'pm-'.uniqid(),
        'business_name' => 'مورد طريقة دفع',
    ]);

    $payment = SupplierPayment::create([
        'supplier_id' => $supplier->id,
        'amount' => 100,
        'currency_code' => 'ILS',
        'paid_at' => '2025-06-01 10:00:00',
        'method' => 'نقداً',
        'recorded_by_user_id' => $accountant->id,
    ]);

    Livewire::actingAs($accountant)
        ->test(SupplierPaymentForm::class, ['supplierPayment' => $payment])
        ->assertSet('payment_method', 'cash')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('supplier-payments.index'));

    expect($payment->fresh()->method)->toBe('cash');
});
