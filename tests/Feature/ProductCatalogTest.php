<?php

use App\Models\LegacyCatalogProduct;
use App\Models\User;

test('المستخدم النشط يصل إلى صفحة أرشيف كتالوج المنتجات القديم', function () {
    $user = User::factory()->create(['is_active' => true]);

    LegacyCatalogProduct::create([
        'payload_json' => [
            'flat' => [
                'Name' => 'منتج تجريبي',
                'ProductCode' => 'SKU-1',
                'UnitPrice' => '10',
            ],
        ],
    ]);

    $this->actingAs($user)
        ->get(route('legacy-catalog-products.index'))
        ->assertOk()
        ->assertSee('كتالوج المنتجات')
        ->assertSee('منتج تجريبي');
});

test('المستخدم غير النشط لا يصل إلى أرشيف كتالوج المنتجات', function () {
    $user = User::factory()->create(['is_active' => false]);

    $this->actingAs($user)
        ->get(route('legacy-catalog-products.index'))
        ->assertForbidden();
});
