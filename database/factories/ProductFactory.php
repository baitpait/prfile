<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductCurrencyPrice;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        return [
            'name' => fake()->words(3, true),
            'product_code' => 'SKU-'.fake()->unique()->numerify('#####'),
            'description' => null,
        ];
    }

    public function withIlsPricing(): static
    {
        return $this->afterCreating(function (Product $product) {
            ProductCurrencyPrice::query()->create([
                'product_id' => $product->id,
                'currency_code' => 'ILS',
                'service_cost_price' => 50,
                'min_sale_price' => 80,
                'sale_price' => 100,
            ]);
        });
    }
}
