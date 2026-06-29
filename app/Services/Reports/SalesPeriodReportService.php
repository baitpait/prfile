<?php

namespace App\Services\Reports;

use App\Models\Invoice;
use App\Models\Product;
use App\Services\InvoicePaymentAllocationService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Business Purpose: Issued invoices in a date range with FIFO payment status.
 */
class SalesPeriodReportService
{
    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function rows(ReportPeriodFilters $filters): Collection
    {
        $invoices = $this->baseQuery($filters)
            ->with(['client', 'lines.product'])
            ->orderBy('document_date')
            ->orderBy('id')
            ->get();

        $allocation = (new InvoicePaymentAllocationService)->forInvoices($invoices);

        return $invoices->map(function (Invoice $inv) use ($allocation) {
            $pay = $allocation[$inv->id] ?? null;

            return [
                'id' => $inv->id,
                'date' => $inv->document_date,
                'client_id' => $inv->client_id,
                'client_name' => $inv->client?->displayName() ?? '—',
                'reference' => $inv->legacy_invoice_no ?? '#'.$inv->id,
                'currency' => $inv->currency_code,
                'amount' => (float) $inv->total_amount,
                'payment_status' => $pay['status'] ?? 'unpaid',
                'payment_label' => $pay['label'] ?? 'غير مدفوعة',
                'line_count' => $inv->lines->count(),
            ];
        });
    }

    /**
     * @return array<string, array{total: float, count: int}>
     */
    public function totalsByCurrency(ReportPeriodFilters $filters): array
    {
        $totals = [];

        foreach ($this->rows($filters) as $row) {
            $cur = $row['currency'];
            if (! isset($totals[$cur])) {
                $totals[$cur] = ['total' => 0.0, 'count' => 0];
            }
            $totals[$cur]['total'] += $row['amount'];
            $totals[$cur]['count']++;
        }

        ksort($totals);

        return $totals;
    }

    /** @return list<string> */
    public function currencyOptions(): array
    {
        return Product::billingCurrencies();
    }

    /** @return Builder<Invoice> */
    private function baseQuery(ReportPeriodFilters $filters): Builder
    {
        $query = Invoice::query()
            ->where('status', 'issued')
            ->whereNull('deleted_at')
            ->whereDate('document_date', '>=', $filters->resolvedDateFrom())
            ->whereDate('document_date', '<=', $filters->resolvedDateTo());

        if ($filters->currency !== null) {
            $query->where('currency_code', $filters->currency);
        }

        if ($filters->clientId !== null) {
            $query->where('client_id', $filters->clientId);
        }

        return $query;
    }
}
