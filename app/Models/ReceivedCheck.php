<?php

namespace App\Models;

use App\Services\Finance\ReceivedCheckStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ReceivedCheck extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'client_id',
        'client_payment_id',
        'bank_name',
        'drawer_name',
        'check_number',
        'due_date',
        'amount',
        'currency_code',
        'status',
        'image_path',
        'cleared_at',
        'not_cleared_at',
        'endorsed_supplier_id',
        'supplier_payment_id',
        'endorsed_at',
        'endorsed_employee_id',
        'salary_payment_id',
        'salary_advance_id',
        'notes',
        'recorded_by_user_id',
    ];

    protected $casts = [
        'due_date' => 'date',
        'amount' => 'decimal:4',
        'cleared_at' => 'datetime',
        'not_cleared_at' => 'datetime',
        'endorsed_at' => 'datetime',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function clientPayment(): BelongsTo
    {
        return $this->belongsTo(ClientPayment::class);
    }

    public function endorsedSupplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'endorsed_supplier_id');
    }

    public function supplierPayment(): BelongsTo
    {
        return $this->belongsTo(SupplierPayment::class);
    }

    public function endorsedEmployee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'endorsed_employee_id');
    }

    public function salaryPayment(): BelongsTo
    {
        return $this->belongsTo(SalaryPayment::class);
    }

    public function salaryAdvance(): BelongsTo
    {
        return $this->belongsTo(SalaryAdvance::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by_user_id');
    }

    public function statusLabel(): string
    {
        if ($this->isEndorsed()) {
            return ReceivedCheckStatus::endorsementLabel($this);
        }

        return ReceivedCheckStatus::label($this->status ?? ReceivedCheckStatus::PENDING);
    }

    public function endorsementTargetLabel(): ?string
    {
        if (! $this->isEndorsed()) {
            return null;
        }

        if ($this->endorsed_supplier_id) {
            return $this->endorsedSupplier?->displayName();
        }

        if ($this->endorsed_employee_id) {
            return $this->endorsedEmployee?->displayName();
        }

        return null;
    }

    public function isPending(): bool
    {
        return ($this->status ?? ReceivedCheckStatus::PENDING) === ReceivedCheckStatus::PENDING;
    }

    public function isEndorsed(): bool
    {
        return ($this->status ?? '') === ReceivedCheckStatus::ENDORSED;
    }

    /**
     * Business Purpose: Human-readable option label for pending-check dropdowns in payment forms.
     */
    public function selectLabel(): string
    {
        return sprintf(
            '%s — %s — %s %s — استحقاق %s',
            $this->check_number,
            $this->client?->displayName() ?? '—',
            number_format((float) $this->amount, 2),
            $this->currency_code,
            $this->due_date?->format('Y-m-d') ?? '—'
        );
    }
}
