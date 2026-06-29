<?php

namespace App\Services\Reports;

use App\Models\Product;
use App\Models\Supplier;
use App\Services\SupplierStatementService;
use Illuminate\Support\Collection;

/**
 * Business Purpose: Period statement summary for all suppliers (R18) — one row per supplier/currency.
 */
class AggregatedSupplierStatementsService
{
    /**
     * @return Collection<int, array{
     *   supplier_id: int,
     *   supplier_name: string,
     *   currency: string,
     *   total_ordered: float,
     *   total_paid: float,
     *   total_adjusted: float,
     *   balance: float,
     *   movement_count: int
     * }>
     */
    public function rows(ReportPeriodFilters $filters): Collection
    {
        $statementService = new SupplierStatementService;
        $dateFrom = $filters->resolvedDateFrom()->format('Y-m-d');
        $dateTo = $filters->resolvedDateTo()->format('Y-m-d');
        $out = collect();

        $query = Supplier::query()->whereNull('deleted_at')->orderBy('business_name');
        if ($filters->supplierId !== null) {
            $query->where('id', $filters->supplierId);
        }

        $query->each(function (Supplier $supplier) use ($statementService, $dateFrom, $dateTo, $filters, &$out): void {
            $statement = $statementService->forSupplier($supplier, $dateFrom, $dateTo);

            foreach ($statement as $currency => $section) {
                if ($filters->currency !== null && $currency !== $filters->currency) {
                    continue;
                }

                $movementCount = count($section['timeline'] ?? []);
                $balance = round((float) $section['balance'], 2);

                if ($movementCount === 0 && abs($balance) < 0.00001) {
                    continue;
                }

                $out->push([
                    'supplier_id' => $supplier->id,
                    'supplier_name' => $supplier->displayName(),
                    'currency' => $currency,
                    'total_ordered' => round((float) $section['total_ordered'], 2),
                    'total_paid' => round((float) $section['total_paid'], 2),
                    'total_adjusted' => round((float) $section['total_adjusted'], 2),
                    'balance' => $balance,
                    'movement_count' => $movementCount,
                ]);
            }
        });

        return $out->sortBy('supplier_name')->sortByDesc('balance')->values();
    }

    /** @return array<string, array{ordered: float, paid: float, adjusted: float, balance: float, suppliers: int}> */
    public function totalsByCurrency(ReportPeriodFilters $filters): array
    {
        $totals = [];

        foreach ($this->rows($filters) as $row) {
            $cur = $row['currency'];
            if (! isset($totals[$cur])) {
                $totals[$cur] = ['ordered' => 0.0, 'paid' => 0.0, 'adjusted' => 0.0, 'balance' => 0.0, 'suppliers' => 0];
            }
            $totals[$cur]['ordered'] += $row['total_ordered'];
            $totals[$cur]['paid'] += $row['total_paid'];
            $totals[$cur]['adjusted'] += $row['total_adjusted'];
            $totals[$cur]['balance'] += $row['balance'];
            $totals[$cur]['suppliers']++;
        }

        ksort($totals);

        return $totals;
    }

    /** @return list<string> */
    public function currencyOptions(): array
    {
        return Product::billingCurrencies();
    }
}
