<?php

namespace App\Services\Reports;

use App\Models\Product;
use App\Models\Supplier;
use App\Services\SupplierStatementService;
use Illuminate\Support\Collection;

/**
 * Business Purpose: Simple supplier balance list as of a date — no aging buckets (R13).
 */
class SupplierPayablesSummaryService
{
    /**
     * @return Collection<int, array{
     *   supplier_id: int,
     *   supplier_name: string,
     *   phone: string|null,
     *   currency: string,
     *   total_ordered: float,
     *   total_paid: float,
     *   total_adjusted: float,
     *   balance: float
     * }>
     */
    public function rows(AsOfSummaryFilters $filters): Collection
    {
        $statementService = new SupplierStatementService;
        $dateTo = $filters->asOfDate->format('Y-m-d');
        $out = collect();

        $query = Supplier::query()->whereNull('deleted_at')->orderBy('business_name');
        if ($filters->supplierId !== null) {
            $query->where('id', $filters->supplierId);
        }

        $query->each(function (Supplier $supplier) use ($statementService, $dateTo, $filters, &$out): void {
            if ($filters->search !== null && ! $this->matchesSearch($supplier, $filters->search)) {
                return;
            }

            $statement = $statementService->forSupplier($supplier, null, $dateTo);

            foreach ($statement as $currency => $section) {
                if ($filters->currency !== null && $currency !== $filters->currency) {
                    continue;
                }

                $balance = round((float) $section['balance'], 2);
                if ($balance <= 0.00001) {
                    continue;
                }

                if ($filters->minBalance !== null && $balance < $filters->minBalance) {
                    continue;
                }

                $out->push([
                    'supplier_id' => $supplier->id,
                    'supplier_name' => $supplier->displayName(),
                    'phone' => $this->displayPhone($supplier),
                    'currency' => $currency,
                    'total_ordered' => round((float) $section['total_ordered'], 2),
                    'total_paid' => round((float) $section['total_paid'], 2),
                    'total_adjusted' => round((float) $section['total_adjusted'], 2),
                    'balance' => $balance,
                ]);
            }
        });

        return $out->sortByDesc('balance')->values();
    }

    /** @return array<string, float> */
    public function totalsByCurrency(AsOfSummaryFilters $filters): array
    {
        $totals = [];

        foreach ($this->rows($filters) as $row) {
            $cur = $row['currency'];
            $totals[$cur] = ($totals[$cur] ?? 0.0) + $row['balance'];
        }

        ksort($totals);

        return $totals;
    }

    /** @return array<string, float> */
    public function aggregateBalancesAsOf(string $dateTo, ?string $currency = null): array
    {
        $filters = new AsOfSummaryFilters(
            asOfDate: \Carbon\Carbon::parse($dateTo)->startOfDay(),
            currency: $currency,
            supplierId: null,
        );

        return $this->totalsByCurrency($filters);
    }

    /** @return list<string> */
    public function currencyOptions(): array
    {
        return Product::billingCurrencies();
    }

    private function matchesSearch(Supplier $supplier, string $search): bool
    {
        $needle = mb_strtolower(trim($search));

        return str_contains(mb_strtolower($supplier->displayName()), $needle)
            || str_contains(mb_strtolower((string) ($supplier->phone_primary ?? '')), $needle)
            || str_contains(mb_strtolower((string) ($supplier->phone_secondary ?? '')), $needle);
    }

    private function displayPhone(Supplier $supplier): ?string
    {
        $primary = trim((string) ($supplier->phone_primary ?? ''));

        return $primary !== '' ? $primary : (trim((string) ($supplier->phone_secondary ?? '')) ?: null);
    }
}
