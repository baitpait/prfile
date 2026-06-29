<?php

namespace App\Services\Reports;

use App\Models\SupplierBalanceAdjustment;
use App\Models\Product;
use Illuminate\Support\Collection;

/**
 * Business Purpose: Supplier balance adjustments in a date range (not cash flow).
 */
class SupplierAdjustmentsPeriodReportService
{
    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function rows(ReportPeriodFilters $filters): Collection
    {
        $query = SupplierBalanceAdjustment::query()
            ->with('supplier')
            ->whereNull('deleted_at')
            ->whereDate('adjustment_date', '>=', $filters->resolvedDateFrom())
            ->whereDate('adjustment_date', '<=', $filters->resolvedDateTo());

        if ($filters->currency !== null) {
            $query->where('currency_code', $filters->currency);
        }

        if ($filters->supplierId !== null) {
            $query->where('supplier_id', $filters->supplierId);
        }

        return $query->orderBy('adjustment_date')->orderBy('id')->get()->map(fn (SupplierBalanceAdjustment $adj) => [
            'id' => $adj->id,
            'date' => $adj->adjustment_date,
            'supplier_id' => $adj->supplier_id,
            'supplier_name' => $adj->supplier?->displayName() ?? '—',
            'type' => $adj->type,
            'type_label' => $adj->typeLabel(),
            'reason' => $adj->reason,
            'currency' => $adj->currency_code,
            'amount' => (float) $adj->amount,
            'notes' => $adj->notes,
        ]);
    }

    /** @return array<string, float> */
    public function totalsByCurrency(ReportPeriodFilters $filters): array
    {
        $totals = [];

        foreach ($this->rows($filters) as $row) {
            $cur = $row['currency'];
            $totals[$cur] = ($totals[$cur] ?? 0.0) + $row['amount'];
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
