<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExchangeRate extends Model
{
    protected $fillable = [
        'rate_date',
        'currency_code',
        'rate_to_ils',
        'source',
    ];

    protected $casts = [
        'rate_date' => 'date',
        'rate_to_ils' => 'decimal:6',
    ];
}
