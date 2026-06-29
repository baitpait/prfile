<?php

namespace App\Services\Reports;

use App\Models\Expense;
use App\Models\Product;
use Illuminate\Support\Collection;

/**
 * Business Purpose: Detailed expense listing for a date range.
 */
class ExpensesReportService
{
    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function rows(ReportPeriodFilters $filters): Collection
    {
        $query = Expense::query()
            ->with('recordedBy')
            ->whereNull('deleted_at')
            ->whereDate('expense_date', '>=', $filters->resolvedDateFrom())
            ->whereDate('expense_date', '<=', $filters->resolvedDateTo());

        if ($filters->currency !== null) {
            $query->where('currency_code', $filters->currency);
        }

        return $query->orderBy('expense_date')->orderBy('id')->get()->map(fn (Expense $e) => [
            'id' => $e->id,
            'date' => $e->expense_date,
            'description' => $e->description,
            'currency' => $e->currency_code,
            'amount' => (float) $e->amount,
            'recorded_by' => $e->recordedBy?->full_name ?? '—',
            'notes' => $e->notes,
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
