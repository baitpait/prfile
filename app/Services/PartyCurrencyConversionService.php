<?php

namespace App\Services;

use App\Models\Client;
use App\Models\ClientBalanceAdjustment;
use App\Models\ClientPayment;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\SupplierBalanceAdjustment;
use App\Models\SupplierPayment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

/**
 * Business Purpose: Re-label all party ledger rows from one currency code to another without changing amounts (no FX).
 */
class PartyCurrencyConversionService
{
    /**
     * @return list<string>
     */
    public function currenciesForClient(Client $client): array
    {
        return $this->distinctCurrencyCodes(
            Invoice::query()->where('client_id', $client->id)->whereNull('deleted_at'),
            ClientPayment::query()->where('client_id', $client->id)->whereNull('deleted_at'),
            ClientBalanceAdjustment::query()->where('client_id', $client->id)->whereNull('deleted_at'),
        );
    }

    /**
     * @return list<string>
     */
    public function currenciesForSupplier(Supplier $supplier): array
    {
        return $this->distinctCurrencyCodes(
            PurchaseOrder::query()->where('supplier_id', $supplier->id)->whereNull('deleted_at'),
            SupplierPayment::query()->where('supplier_id', $supplier->id)->whereNull('deleted_at'),
            SupplierBalanceAdjustment::query()->where('supplier_id', $supplier->id)->whereNull('deleted_at'),
        );
    }

    /**
     * @return array{invoices: int, payments: int, adjustments: int, total: int}
     */
    public function previewClient(Client $client, string $fromCurrency, string $toCurrency): array
    {
        $this->assertDistinctCurrencies($fromCurrency, $toCurrency);

        $counts = [
            'invoices' => Invoice::query()
                ->where('client_id', $client->id)
                ->where('currency_code', $fromCurrency)
                ->whereNull('deleted_at')
                ->count(),
            'payments' => ClientPayment::query()
                ->where('client_id', $client->id)
                ->where('currency_code', $fromCurrency)
                ->whereNull('deleted_at')
                ->count(),
            'adjustments' => ClientBalanceAdjustment::query()
                ->where('client_id', $client->id)
                ->where('currency_code', $fromCurrency)
                ->whereNull('deleted_at')
                ->count(),
        ];
        $counts['total'] = $counts['invoices'] + $counts['payments'] + $counts['adjustments'];

        return $counts;
    }

    /**
     * @return array{invoices: int, payments: int, adjustments: int, total: int}
     */
    public function previewSupplier(Supplier $supplier, string $fromCurrency, string $toCurrency): array
    {
        $this->assertDistinctCurrencies($fromCurrency, $toCurrency);

        $counts = [
            'invoices' => PurchaseOrder::query()
                ->where('supplier_id', $supplier->id)
                ->where('currency_code', $fromCurrency)
                ->whereNull('deleted_at')
                ->count(),
            'payments' => SupplierPayment::query()
                ->where('supplier_id', $supplier->id)
                ->where('currency_code', $fromCurrency)
                ->whereNull('deleted_at')
                ->count(),
            'adjustments' => SupplierBalanceAdjustment::query()
                ->where('supplier_id', $supplier->id)
                ->where('currency_code', $fromCurrency)
                ->whereNull('deleted_at')
                ->count(),
        ];
        $counts['total'] = $counts['invoices'] + $counts['payments'] + $counts['adjustments'];

        return $counts;
    }

    /**
     * @return array{invoices: int, payments: int, adjustments: int, total: int}
     */
    public function applyClient(Client $client, string $fromCurrency, string $toCurrency, int $userId): array
    {
        $this->assertDistinctCurrencies($fromCurrency, $toCurrency);

        $counts = $this->previewClient($client, $fromCurrency, $toCurrency);
        if ($counts['total'] === 0) {
            throw new InvalidArgumentException('لا توجد سجلات بالعملة المحددة.');
        }

        $updated = DB::transaction(function () use ($client, $fromCurrency, $toCurrency) {
            return [
                'invoices' => Invoice::query()
                    ->where('client_id', $client->id)
                    ->where('currency_code', $fromCurrency)
                    ->whereNull('deleted_at')
                    ->update(['currency_code' => $toCurrency]),
                'payments' => ClientPayment::query()
                    ->where('client_id', $client->id)
                    ->where('currency_code', $fromCurrency)
                    ->whereNull('deleted_at')
                    ->update(['currency_code' => $toCurrency]),
                'adjustments' => ClientBalanceAdjustment::query()
                    ->where('client_id', $client->id)
                    ->where('currency_code', $fromCurrency)
                    ->whereNull('deleted_at')
                    ->update(['currency_code' => $toCurrency]),
            ];
        });

        $updated['total'] = $updated['invoices'] + $updated['payments'] + $updated['adjustments'];

        Log::info('party_currency_conversion', [
            'party_type' => 'client',
            'party_id' => $client->id,
            'from' => $fromCurrency,
            'to' => $toCurrency,
            'user_id' => $userId,
            'counts' => $updated,
            'amounts_unchanged' => true,
        ]);

        return $updated;
    }

    /**
     * @return array{invoices: int, payments: int, adjustments: int, total: int}
     */
    public function applySupplier(Supplier $supplier, string $fromCurrency, string $toCurrency, int $userId): array
    {
        $this->assertDistinctCurrencies($fromCurrency, $toCurrency);

        $counts = $this->previewSupplier($supplier, $fromCurrency, $toCurrency);
        if ($counts['total'] === 0) {
            throw new InvalidArgumentException('لا توجد سجلات بالعملة المحددة.');
        }

        $updated = DB::transaction(function () use ($supplier, $fromCurrency, $toCurrency) {
            return [
                'invoices' => PurchaseOrder::query()
                    ->where('supplier_id', $supplier->id)
                    ->where('currency_code', $fromCurrency)
                    ->whereNull('deleted_at')
                    ->update(['currency_code' => $toCurrency]),
                'payments' => SupplierPayment::query()
                    ->where('supplier_id', $supplier->id)
                    ->where('currency_code', $fromCurrency)
                    ->whereNull('deleted_at')
                    ->update(['currency_code' => $toCurrency]),
                'adjustments' => SupplierBalanceAdjustment::query()
                    ->where('supplier_id', $supplier->id)
                    ->where('currency_code', $fromCurrency)
                    ->whereNull('deleted_at')
                    ->update(['currency_code' => $toCurrency]),
            ];
        });

        $updated['total'] = $updated['invoices'] + $updated['payments'] + $updated['adjustments'];

        Log::info('party_currency_conversion', [
            'party_type' => 'supplier',
            'party_id' => $supplier->id,
            'from' => $fromCurrency,
            'to' => $toCurrency,
            'user_id' => $userId,
            'counts' => $updated,
            'amounts_unchanged' => true,
        ]);

        return $updated;
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<\Illuminate\Database\Eloquent\Model>  ...$queries
     * @return list<string>
     */
    private function distinctCurrencyCodes(...$queries): array
    {
        $codes = collect();

        foreach ($queries as $query) {
            $codes = $codes->merge(
                (clone $query)->whereNotNull('currency_code')->distinct()->pluck('currency_code')
            );
        }

        return $codes->filter(fn ($c) => $c !== null && $c !== '')
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    private function assertDistinctCurrencies(string $fromCurrency, string $toCurrency): void
    {
        $from = strtoupper(trim($fromCurrency));
        $to = strtoupper(trim($toCurrency));

        if ($from === '' || $to === '') {
            throw new InvalidArgumentException('العملة مطلوبة.');
        }

        if (! in_array($from, Product::billingCurrencies(), true) || ! in_array($to, Product::billingCurrencies(), true)) {
            throw new InvalidArgumentException('عملة غير معتمدة.');
        }

        if ($from === $to) {
            throw new InvalidArgumentException('العملتان متطابقتان.');
        }
    }
}
