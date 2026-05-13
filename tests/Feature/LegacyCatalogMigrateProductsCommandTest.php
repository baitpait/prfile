<?php

use App\Models\LegacyCatalogProduct;
use App\Models\Product;
use App\Models\ProductCurrencyPrice;

test('أمر ترحيل الأرشيف ينشئ منتجًا بتسعير شيكل فقط', function () {
    LegacyCatalogProduct::query()->create([
        'id' => 9001,
        'payload_json' => [
            'flat' => [
                'Name' => 'بند اختبار ترحيل',
                'ProductCode' => 'T-RM-9001',
                'Description' => 'وصف',
                'UnitPrice' => '120.50',
                'BuyPrice' => '80',
            ],
        ],
    ]);

    $this->artisan('catalog:migrate-legacy-products')->assertSuccessful();

    $product = Product::query()->where('imported_from_legacy_catalog_id', 9001)->first();
    expect($product)->not->toBeNull()
        ->and($product->name)->toBe('بند اختبار ترحيل');

    $ils = ProductCurrencyPrice::query()
        ->where('product_id', $product->id)
        ->where('currency_code', 'ILS')
        ->first();

    expect($ils)->not->toBeNull()
        ->and((float) $ils->sale_price)->toBe(120.5)
        ->and((float) $ils->service_cost_price)->toBe(80.0)
        ->and((float) $ils->min_sale_price)->toBe(0.0);

    expect(
        ProductCurrencyPrice::query()
            ->where('product_id', $product->id)
            ->whereIn('currency_code', ['JOD', 'USD', 'EUR'])
            ->count()
    )->toBe(0);
});
