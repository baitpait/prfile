<?php

use App\Livewire\PaymentForm;
use App\Models\Client;
use App\Models\User;
use Livewire\Livewire;

test('payment form filters clients by search text', function () {
    $accountant = User::factory()->create(['role' => 'accountant', 'is_active' => true]);

    Client::factory()->create(['business_name' => 'شركة ألفا للإعلام', 'phone_primary' => '+972-50-111']);
    Client::factory()->create(['business_name' => 'متجر بيتا', 'phone_primary' => '+972-52-222']);

    Livewire::actingAs($accountant)
        ->test(PaymentForm::class)
        ->set('clientSearch', 'ألفا')
        ->assertSee('شركة ألفا للإعلام')
        ->assertDontSee('متجر بيتا');
});

test('payment form finds client by phone in search', function () {
    $accountant = User::factory()->create(['role' => 'accountant', 'is_active' => true]);

    Client::factory()->create(['business_name' => 'عميل هاتف', 'phone_primary' => '0599123456']);

    Livewire::actingAs($accountant)
        ->test(PaymentForm::class)
        ->set('clientSearch', '0599123456')
        ->assertSee('عميل هاتف');
});
