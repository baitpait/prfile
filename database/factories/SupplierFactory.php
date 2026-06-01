<?php

namespace Database\Factories;

use App\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;

class SupplierFactory extends Factory
{
    protected $model = Supplier::class;

    public function definition(): array
    {
        return [
            'business_name' => fake()->company(),
            'email' => fake()->unique()->safeEmail(),
            'phone_primary' => fake()->phoneNumber(),
        ];
    }
}
