<?php

namespace Database\Factories;

use App\Models\Client;
use Illuminate\Database\Eloquent\Factories\Factory;

class ClientFactory extends Factory
{
    protected $model = Client::class;

    public function definition(): array
    {
        return [
            'business_name' => fake()->company(),
            'email'         => fake()->unique()->safeEmail(),
            'phone_primary' => fake()->phoneNumber(),
        ];
    }
}
