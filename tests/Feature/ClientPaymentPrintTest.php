<?php

use App\Models\ClientPayment;
use App\Models\User;

test('authenticated user can print client payment voucher', function () {
    $user = User::factory()->create(['role' => 'viewer', 'is_active' => true]);
    $payment = ClientPayment::factory()->create();

    $this->actingAs($user)
        ->get(route('payments.print', $payment))
        ->assertOk()
        ->assertSee('سند قبض', false);
});

test('guest cannot print client payment voucher', function () {
    $payment = ClientPayment::factory()->create();

    $this->get(route('payments.print', $payment))->assertRedirect();
});
