<?php

namespace Database\Factories;

use App\Models\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;

class EmployeeFactory extends Factory
{
    protected $model = Employee::class;

    public function definition(): array
    {
        return [
            'employee_code' => 'EMP-'.fake()->unique()->numerify('####'),
            'full_name' => fake()->name(),
            'phone_primary' => fake()->numerify('05########'),
            'email' => fake()->optional()->safeEmail(),
            'job_title' => fake()->randomElement(['مصمم', 'محرر', 'محاسب', 'فني']),
            'department' => fake()->randomElement(['إنتاج', 'مبيعات', 'إدارة']),
            'hire_date' => fake()->dateTimeBetween('-3 years', 'now'),
            'base_salary_amount' => fake()->randomFloat(2, 3000, 12000),
            'base_salary_currency' => 'ILS',
            'pay_frequency' => Employee::PAY_FREQUENCY_MONTHLY,
            'is_active' => true,
            'recorded_by_user_id' => null,
        ];
    }
}
