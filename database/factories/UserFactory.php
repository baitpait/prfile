<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        return [
            'full_name' => fake()->name(),
            'email'     => fake()->unique()->safeEmail(),
            'password'  => bcrypt('password'),
            'role'      => 'viewer',
            'is_active' => true,
        ];
    }
}
