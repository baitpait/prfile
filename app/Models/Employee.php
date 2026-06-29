<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employee extends Model
{
    use HasFactory, SoftDeletes;

    public const PAY_FREQUENCY_MONTHLY = 'monthly';

    public const PAY_FREQUENCY_PART_TIME = 'part_time';

    /** @return array<string, string> */
    public static function employmentTypeOptions(): array
    {
        return [
            self::PAY_FREQUENCY_MONTHLY => 'شهري',
            self::PAY_FREQUENCY_PART_TIME => 'بارت تايم',
        ];
    }

    public static function employmentTypeLabel(?string $type): string
    {
        return self::employmentTypeOptions()[$type ?? self::PAY_FREQUENCY_MONTHLY]
            ?? self::employmentTypeOptions()[self::PAY_FREQUENCY_MONTHLY];
    }

    protected $fillable = [
        'employee_code',
        'full_name',
        'phone_primary',
        'phone_secondary',
        'email',
        'national_id',
        'job_title',
        'department',
        'hire_date',
        'termination_date',
        'base_salary_amount',
        'base_salary_currency',
        'pay_frequency',
        'bank_name',
        'bank_account',
        'notes',
        'is_active',
        'recorded_by_user_id',
    ];

    protected $casts = [
        'hire_date' => 'date',
        'termination_date' => 'date',
        'base_salary_amount' => 'decimal:4',
        'is_active' => 'boolean',
    ];

    public function displayName(): string
    {
        return $this->full_name ?: "موظف #{$this->id}";
    }

    public function salaryPayments(): HasMany
    {
        return $this->hasMany(SalaryPayment::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by_user_id');
    }
}
