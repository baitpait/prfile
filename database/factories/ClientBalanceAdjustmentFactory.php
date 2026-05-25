<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\ClientBalanceAdjustment;
use Illuminate\Database\Eloquent\Factories\Factory;

class ClientBalanceAdjustmentFactory extends Factory
{
    protected $model = ClientBalanceAdjustment::class;

    public function definition(): array
    {
        return [
            'client_id' => Client::factory(),
            'amount' => fake()->randomFloat(2, 10, 500),
            'currency_code' => 'ILS',
            'adjustment_date' => fake()->dateTimeBetween('-1 year', 'now'),
            'type' => ClientBalanceAdjustment::TYPE_SETTLEMENT_DISCOUNT,
            'reason' => 'خصم تسوية',
            'recorded_by_user_id' => null,
        ];
    }
}
