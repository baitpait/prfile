<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class SalaryAdvance extends Model
{
    use SoftDeletes;

    public const STATUS_PAID = 'paid';

    public const STATUS_SETTLED = 'settled';

    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'employee_id',
        'amount',
        'currency_code',
        'paid_at',
        'method',
        'bank_reference',
        'status',
        'settled_salary_payment_id',
        'notes',
        'recorded_by_user_id',
    ];

    protected $casts = [
        'amount' => 'decimal:4',
        'paid_at' => 'date',
    ];

    public static function statusLabel(string $status): string
    {
        return match ($status) {
            self::STATUS_SETTLED => 'مُسوّاة',
            self::STATUS_CANCELLED => 'ملغاة',
            default => 'مدفوعة',
        };
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by_user_id');
    }

    public function settledSalaryPayment(): BelongsTo
    {
        return $this->belongsTo(SalaryPayment::class, 'settled_salary_payment_id');
    }

    public function receivedCheck(): HasOne
    {
        return $this->hasOne(ReceivedCheck::class);
    }
}
