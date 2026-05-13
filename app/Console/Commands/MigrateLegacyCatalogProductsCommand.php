<?php

namespace App\Console\Commands;

use App\Models\LegacyCatalogProduct;
use App\Models\Product;
use App\Models\ProductCurrencyPrice;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MigrateLegacyCatalogProductsCommand extends Command
{
    protected $signature = 'catalog:migrate-legacy-products';

    protected $description = 'ترحيل منتجات legacy_catalog_products إلى كتالوج المبيعات: سعر الشيكل من UnitPrice وBuyPrice، دون صفوف تسعير لـ JOD/USD/EUR.';

    public function handle(): int
    {
        $count = LegacyCatalogProduct::query()->count();
        if ($count === 0) {
            $this->warn('لا توجد صفوف في legacy_catalog_products.');

            return self::SUCCESS;
        }

        $this->info("معالجة {$count} صفًا من الأرشيف…");

        $created = 0;
        $updated = 0;
        $skipped = 0;

        foreach (LegacyCatalogProduct::query()->orderBy('id')->cursor() as $legacy) {
            $flat = $legacy->flat();
            $name = $this->parseOptionalText($flat['Name'] ?? null) ?? '';
            if ($name === '') {
                $skipped++;
                $this->line("  تخطّي legacy #{$legacy->id}: لا يوجد اسم");

                continue;
            }

            $sale = $this->parseMoney($flat['UnitPrice'] ?? null);
            $cost = $this->parseMoney($flat['BuyPrice'] ?? null);

            if ($sale === null && $cost === null) {
                $skipped++;
                $this->line("  تخطّي legacy #{$legacy->id}: لا يوجد UnitPrice ولا BuyPrice");

                continue;
            }

            if ($sale === null) {
                $sale = $cost ?? 0.0;
            }
            if ($cost === null) {
                $cost = 0.0;
            }

            $sale = max(0, $sale);
            $cost = max(0, $cost);

            $minRaw = $this->parseMoney($flat['MinimumPrice'] ?? null);
            if ($minRaw !== null) {
                $minSale = max(0.0, min($minRaw, $sale));
            } else {
                $minSale = 0.0;
            }

            $description = $this->parseOptionalText($flat['Description'] ?? null);

            $xmlCode = $this->parseOptionalText($flat['ProductCode'] ?? null) ?? '';

            DB::transaction(function () use (
                $legacy,
                $name,
                $xmlCode,
                $description,
                $sale,
                $minSale,
                $cost,
                &$created,
                &$updated
            ): void {
                $product = Product::withTrashed()->firstOrNew([
                    'imported_from_legacy_catalog_id' => $legacy->id,
                ]);

                $wasNew = ! $product->exists;

                if ($product->trashed()) {
                    $product->restore();
                }

                $productCode = $this->resolveProductCode($xmlCode, $legacy->id, $product->id);
                $product->fill([
                    'imported_from_legacy_catalog_id' => $legacy->id,
                    'name' => $name,
                    'product_code' => $productCode,
                    'description' => $description,
                ]);
                $product->save();

                if ($wasNew) {
                    $created++;
                } else {
                    $updated++;
                }

                ProductCurrencyPrice::query()->updateOrCreate(
                    [
                        'product_id' => $product->id,
                        'currency_code' => 'ILS',
                    ],
                    [
                        'sale_price' => $sale,
                        'min_sale_price' => $minSale,
                        'service_cost_price' => $cost,
                    ]
                );

                ProductCurrencyPrice::query()
                    ->where('product_id', $product->id)
                    ->whereIn('currency_code', ['JOD', 'USD', 'EUR'])
                    ->delete();
            });
        }

        $this->info("تم: إنشاء {$created}، تحديث {$updated}، تخطّي {$skipped}.");

        return self::SUCCESS;
    }

    private function resolveProductCode(string $xmlCode, int $legacyId, ?int $currentProductId): string
    {
        $candidates = [];
        if ($xmlCode !== '') {
            $candidates[] = $xmlCode;
            if (strlen($xmlCode.'-'.$legacyId) <= 64) {
                $candidates[] = $xmlCode.'-'.$legacyId;
            }
        }
        $candidates[] = 'LEGACY-'.$legacyId;

        foreach ($candidates as $code) {
            $q = Product::query()->where('product_code', $code);
            if ($currentProductId) {
                $q->where('id', '!=', $currentProductId);
            }
            if (! $q->exists()) {
                return $code;
            }
        }

        return 'LEGACY-'.$legacyId;
    }

    private function parseMoney(mixed $value): ?float
    {
        if ($value === null || is_array($value)) {
            return null;
        }
        $s = trim((string) $value);
        if ($s === '') {
            return null;
        }
        $s = str_replace(["\u{00A0}", ' ', ','], ['', '', '.'], $s);
        if (! is_numeric($s)) {
            return null;
        }

        return (float) $s;
    }

    private function parseOptionalText(mixed $value): ?string
    {
        if ($value === null || is_array($value)) {
            return null;
        }
        $s = trim((string) $value);

        return $s !== '' ? $s : null;
    }
}
