<?php

use App\Livewire\ClientList;
use App\Livewire\SupplierList;
use App\Models\Client;
use App\Models\Supplier;
use App\Models\User;
use Livewire\Livewire;

test('client list filters by business name search', function () {
    $user = User::factory()->create(['role' => 'accountant', 'is_active' => true]);

    Client::factory()->create(['business_name' => 'شركة ألفا للإعلام']);
    Client::factory()->create(['business_name' => 'متجر بيتا']);

    Livewire::actingAs($user)
        ->test(ClientList::class)
        ->set('searchDraft', 'ألفا')
        ->call('applyPartyFilters')
        ->assertSee('شركة ألفا للإعلام')
        ->assertDontSee('متجر بيتا');
});

test('client list filters by city', function () {
    $user = User::factory()->create(['role' => 'accountant', 'is_active' => true]);

    Client::factory()->create(['business_name' => 'عميل رام الله', 'city' => 'رام الله']);
    Client::factory()->create(['business_name' => 'عميل نابلس', 'city' => 'نابلس']);

    Livewire::actingAs($user)
        ->test(ClientList::class)
        ->set('filterCityDraft', 'رام الله')
        ->call('applyPartyFilters')
        ->assertSee('عميل رام الله')
        ->assertDontSee('عميل نابلس');
});

test('supplier list filters by business name search', function () {
    $user = User::factory()->create(['role' => 'accountant', 'is_active' => true]);

    Supplier::factory()->create(['business_name' => 'مطبعة جاما']);
    Supplier::factory()->create(['business_name' => 'ورشة دلتا']);

    Livewire::actingAs($user)
        ->test(SupplierList::class)
        ->set('searchDraft', 'جاما')
        ->call('applyPartyFilters')
        ->assertSee('مطبعة جاما')
        ->assertDontSee('ورشة دلتا');
});

test('party directory search draft does not filter until apply is clicked', function () {
    $user = User::factory()->create(['role' => 'accountant', 'is_active' => true]);

    Client::factory()->create(['business_name' => 'شركة ألفا للإعلام']);
    Client::factory()->create(['business_name' => 'متجر بيتا']);

    Livewire::actingAs($user)
        ->test(ClientList::class)
        ->set('searchDraft', 'ألفا')
        ->assertSee('متجر بيتا')
        ->call('applyPartyFilters')
        ->assertDontSee('متجر بيتا');
});

test('party directory clear filters resets search and city', function () {
    $user = User::factory()->create(['role' => 'accountant', 'is_active' => true]);

    Livewire::actingAs($user)
        ->test(ClientList::class)
        ->set('searchDraft', 'اختبار')
        ->set('filterCityDraft', 'رام الله')
        ->call('clearPartyFilters')
        ->assertSet('search', '')
        ->assertSet('filterCity', '')
        ->assertSet('sort', 'newest');
});
