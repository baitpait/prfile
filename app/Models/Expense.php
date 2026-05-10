<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Expense extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'description', 'amount', 'currency_code',
        'expense_date', 'notes', 'recorded_by_user_id', 'source_row_json',
    ];

    protected $casts = [
        'amount' => 'decimal:4',
        'expense_date' => 'date',
        'source_row_json' => 'array',
    ];

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by_user_id');
    }
}
