<?php

namespace App\Services\Reports;

use App\Models\Product;
use App\Models\SupplierPayment;
use Illuminate\Support\Collection;

/**
 * Business Purpose: Detailed supplier payment listing for a date range.
 */
class SupplierPaymentsReportService
{
    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function rows(ReportPeriodFilters $filters): Collection
    {
        $query = SupplierPayment::query()
            ->with('supplier')
            ->whereNull('deleted_at')
            ->whereDate('paid_at', '>=', $filters->resolvedDateFrom())
            ->whereDate('paid_at', '<=', $filters->resolvedDateTo());

        if ($filters->currency !== null) {
            $query->where('currency_code', $filters->currency);
        }

        if ($filters->method !== null) {
            $query->where('method', $filters->method);
        }

        if ($filters->supplierId !== null) {
            $query->where('supplier_id', $filters->supplierId);
        }

        return $query->orderBy('paid_at')->orderBy('id')->get()->map(fn (SupplierPayment $p) => [
            'id' => $p->id,
            'date' => $p->paid_at,
            'supplier_id' => $p->supplier_id,
            'supplier_name' => $p->supplier?->displayName() ?? '—',
            'reference' => $p->bank_reference ?? '#'.$p->id,
            'method' => $p->method,
            'method_label' => PaymentMethodLabels::label($p->method),
            'currency' => $p->currency_code,
            'amount' => (float) $p->amount,
            'notes' => $p->notes,
        ]);
    }

    /**
     * @return array<string, float>
     */
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
