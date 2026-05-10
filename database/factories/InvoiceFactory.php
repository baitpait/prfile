<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\Invoice;
use Illuminate\Database\Eloquent\Factories\Factory;

class InvoiceFactory extends Factory
{
    protected $model = Invoice::class;

    public function definition(): array
    {
        return [
            'client_id'     => Client::factory(),
            'currency_code' => 'ILS',
            'total_amount'  => fake()->randomFloat(2, 100, 10000),
            'discount_amount' => 0,
            'status'        => 'issued',
            'document_date' => fake()->dateTimeBetween('-1 year', 'now')->format('Y-m-d'),
        ];
    }
}
