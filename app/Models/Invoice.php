<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'client_id', 'legacy_invoice_no',
        'document_date', 'issue_date', 'due_date',
        'currency_code', 'discount_amount', 'total_amount',
        'notes', 'status', 'recorded_by_user_id', 'source_row_json',
    ];

    protected $casts = [
        'document_date' => 'date',
        'issue_date' => 'date',
        'due_date' => 'date',
        'discount_amount' => 'decimal:4',
        'total_amount' => 'decimal:4',
        'source_row_json' => 'array',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(InvoiceLine::class)->orderBy('line_order');
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by_user_id');
    }
}
