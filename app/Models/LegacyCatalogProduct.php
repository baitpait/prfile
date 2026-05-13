<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * أرشيف منتجات من XML القديم (Products.xml) — لا يُربَط ببنود الفواتير الحالية.
 *
 * @property-read array<string, mixed>|null $payload_json
 */
class LegacyCatalogProduct extends Model
{
    protected $table = 'legacy_catalog_products';

    protected $guarded = [];

    protected $casts = [
        'payload_json' => 'array',
    ];

    /** @return array<string, mixed> */
    public function flat(): array
    {
        $p = $this->payload_json ?? [];
        if (! is_array($p)) {
            return [];
        }
        if (isset($p['flat']) && is_array($p['flat'])) {
            return $p['flat'];
        }

        return $p;
    }

    public function displayName(): string
    {
        $raw = $this->flat()['Name'] ?? null;
        if ($raw === null || is_array($raw)) {
            return '—';
        }
        $name = trim((string) $raw);

        return $name !== '' ? $name : '—';
    }

    public function productCode(): string
    {
        $raw = $this->flat()['ProductCode'] ?? null;
        if ($raw === null || is_array($raw)) {
            return (string) $this->id;
        }
        $c = trim((string) $raw);

        return $c !== '' ? $c : (string) $this->id;
    }
}
