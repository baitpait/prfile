<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceLine extends Model
{
    protected $fillable = [
        'invoice_id', 'product_id', 'line_order', 'title', 'description',
        'unit_price', 'quantity', 'line_total', 'source_row_json',
    ];

    protected $casts = [
        'unit_price' => 'decimal:4',
        'quantity' => 'decimal:4',
        'line_total' => 'decimal:4',
        'source_row_json' => 'array',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Business Purpose: Prefer line description; for legacy imports use linked product catalog text.
     */
    public function displayDetails(): ?string
    {
        $lineDescription = trim((string) ($this->description ?? ''));
        if ($lineDescription !== '') {
            return $lineDescription;
        }

        $productDescription = trim((string) ($this->product?->description ?? ''));

        return $productDescription !== '' ? $productDescription : null;
    }
}
