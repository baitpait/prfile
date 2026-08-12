<?php

namespace App\Models;

use App\Models\Concerns\HasStatementPaymentReference;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class SupplierPayment extends Model
{
    use HasStatementPaymentReference, SoftDeletes;

    protected $fillable = [
        'supplier_id', 'purchase_order_id', 'amount', 'currency_code',
        'paid_at', 'method', 'bank_reference', 'notes',
        'recorded_by_user_id',
    ];

    protected $casts = [
        'amount' => 'decimal:4',
        'paid_at' => 'datetime',
    ];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function receivedCheck(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(ReceivedCheck::class, 'supplier_payment_id');
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by_user_id');
    }
}
