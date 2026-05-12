<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductCurrencyPrice extends Model
{
    protected $fillable = [
        'product_id',
        'currency_code',
        'service_cost_price',
        'min_sale_price',
        'sale_price',
    ];

    protected $casts = [
        'service_cost_price' => 'decimal:4',
        'min_sale_price' => 'decimal:4',
        'sale_price' => 'decimal:4',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
