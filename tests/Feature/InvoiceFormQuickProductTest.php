<?php

use App\Livewire\InvoiceForm;
use App\Models\Client;
use App\Models\Product;
use App\Models\User;
use Livewire\Livewire;

test('إضافة منتج سريع من نموذج الفاتورة ينشئ منتجًا ويربط البند', function () {
    $accountant = User::factory()->create(['role' => 'accountant', 'is_active' => true]);
    $client = Client::factory()->create();

    $this->actingAs($accountant);

    Livewire::test(InvoiceForm::class)
        ->set('client_id', (string) $client->id)
        ->set('currency_code', 'ILS')
        ->set('lines.0.product_search', 'منتج سريع من الفاتورة')
        ->call('openQuickAddForLine', 0)
        ->assertSet('quickAddName', 'منتج سريع من الفاتورة')
        ->set('quickAddSalePrice', '200')
        ->set('quickAddMinSalePrice', '150')
        ->set('quickAddServiceCost', '100')
        ->call('saveQuickAddProduct')
        ->assertHasNoErrors();

    $product = Product::query()->where('name', 'منتج سريع من الفاتورة')->first();
    expect($product)->not->toBeNull();
    expect($product->hasCompletePricingForCurrency('ILS'))->toBeTrue();
});
