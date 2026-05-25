<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Client extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'legacy_number', 'legacy_match_key',
        'business_name', 'first_name', 'last_name',
        'email', 'phone_primary', 'phone_secondary',
        'address_line1', 'address_line2', 'city', 'state_region',
        'postal_code', 'country_code', 'notes',
        'assigned_user_id', 'source_row_json',
    ];

    protected $casts = [
        'source_row_json' => 'array',
    ];

    public function displayName(): string
    {
        return $this->business_name
            ?? trim("{$this->first_name} {$this->last_name}")
            ?: "عميل #{$this->id}";
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(ClientContact::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(ClientPayment::class);
    }

    public function balanceAdjustments(): HasMany
    {
        return $this->hasMany(ClientBalanceAdjustment::class);
    }
}
