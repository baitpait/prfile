<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class SupplierBalanceAdjustment extends Model
{
    use SoftDeletes;

    public const TYPE_SETTLEMENT_DISCOUNT = 'settlement_discount';

    public const TYPE_WRITE_OFF = 'write_off';

    public const TYPE_OTHER = 'other';

    protected $fillable = [
        'supplier_id',
        'amount',
        'currency_code',
        'adjustment_date',
        'type',
        'reason',
        'notes',
        'recorded_by_user_id',
    ];

    protected $casts = [
        'amount' => 'decimal:4',
        'adjustment_date' => 'date',
    ];

    /** @return array<string, string> */
    public static function typeLabels(): array
    {
        return [
            self::TYPE_SETTLEMENT_DISCOUNT => 'خصم تسوية',
            self::TYPE_WRITE_OFF => 'إعفاء',
            self::TYPE_OTHER => 'أخرى',
        ];
    }

    public function typeLabel(): string
    {
        return self::typeLabels()[$this->type] ?? $this->type;
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by_user_id');
    }
}
