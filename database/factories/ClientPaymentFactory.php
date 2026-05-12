<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\ClientPayment;
use Illuminate\Database\Eloquent\Factories\Factory;

class ClientPaymentFactory extends Factory
{
    protected $model = ClientPayment::class;

    public function definition(): array
    {
        return [
            'client_id' => Client::factory(),
            'amount' => fake()->randomFloat(2, 50, 5000),
            'currency_code' => 'ILS',
            'paid_at' => fake()->dateTimeBetween('-1 year', 'now'),
            'method' => fake()->randomElement(['تحويل بنكي', 'شيك', 'نقداً']),
        ];
    }
}
