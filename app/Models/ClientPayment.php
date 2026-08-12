<?php

namespace App\Models;

use App\Models\Concerns\HasStatementPaymentReference;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ClientPayment extends Model
{
    use HasFactory, HasStatementPaymentReference, SoftDeletes;

    protected $fillable = [
        'client_id', 'invoice_id', 'amount', 'currency_code',
        'paid_at', 'method', 'bank_reference', 'notes',
        'recorded_by_user_id',
    ];

    protected $casts = [
        'amount' => 'decimal:4',
        'paid_at' => 'datetime',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by_user_id');
    }

    public function receivedCheck(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(ReceivedCheck::class, 'client_payment_id');
    }
}
