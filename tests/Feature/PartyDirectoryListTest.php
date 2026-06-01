<?php

use App\Livewire\ClientList;
use App\Livewire\SupplierList;
use App\Models\Client;
use App\Models\Supplier;
use App\Models\User;
use Livewire\Livewire;

test('client list shows all clients', function () {
    $user = User::factory()->create(['role' => 'accountant', 'is_active' => true]);

    Client::factory()->create(['business_name' => 'شركة ألفا للإعلام']);
    Client::factory()->create(['business_name' => 'متجر بيتا']);

    Livewire::actingAs($user)
        ->test(ClientList::class)
        ->assertSee('شركة ألفا للإعلام')
        ->assertSee('متجر بيتا');
});

test('client list filters by name search', function () {
    $user = User::factory()->create(['role' => 'accountant', 'is_active' => true]);

    Client::factory()->create(['business_name' => 'شركة ألفا للإعلام']);
    Client::factory()->create(['business_name' => 'متجر بيتا']);

    Livewire::actingAs($user)
        ->test(ClientList::class)
        ->set('search', 'ألفا')
        ->assertSee('شركة ألفا للإعلام')
        ->assertDontSee('متجر بيتا');
});

test('client list name search ignores phone and city', function () {
    $user = User::factory()->create(['role' => 'accountant', 'is_active' => true]);

    Client::factory()->create(['business_name' => 'شركة ألفا', 'phone_primary' => '0599123456', 'city' => 'رام الله']);
    Client::factory()->create(['business_name' => 'متجر بيتا', 'phone_primary' => '0599765432', 'city' => 'نابلس']);

    Livewire::actingAs($user)
        ->test(ClientList::class)
        ->set('search', '0599123456')
        ->assertDontSee('شركة ألفا')
        ->assertDontSee('متجر بيتا')
        ->assertSee('لا توجد نتائج للبحث');
});

test('supplier list shows all suppliers', function () {
    $user = User::factory()->create(['role' => 'accountant', 'is_active' => true]);

    Supplier::factory()->create(['business_name' => 'مطبعة جاما']);
    Supplier::factory()->create(['business_name' => 'ورشة دلتا']);

    Livewire::actingAs($user)
        ->test(SupplierList::class)
        ->assertSee('مطبعة جاما')
        ->assertSee('ورشة دلتا');
});

test('supplier list filters by name search', function () {
    $user = User::factory()->create(['role' => 'accountant', 'is_active' => true]);

    Supplier::factory()->create(['business_name' => 'مطبعة جاما']);
    Supplier::factory()->create(['business_name' => 'ورشة دلتا']);

    Livewire::actingAs($user)
        ->test(SupplierList::class)
        ->set('search', 'جاما')
        ->assertSee('مطبعة جاما')
        ->assertDontSee('ورشة دلتا');
});
