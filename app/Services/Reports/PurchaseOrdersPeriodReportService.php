<?php

namespace App\Services\Reports;

use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Services\PurchaseOrderPaymentAllocationService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Business Purpose: Issued purchase orders in a date range with FIFO payment status.
 */
class PurchaseOrdersPeriodReportService
{
    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function rows(ReportPeriodFilters $filters): Collection
    {
        $orders = $this->baseQuery($filters)
            ->with(['supplier', 'lines'])
            ->orderBy('document_date')
            ->orderBy('id')
            ->get();

        $allocation = (new PurchaseOrderPaymentAllocationService)->forPurchaseOrders($orders);

        return $orders->map(function (PurchaseOrder $po) use ($allocation) {
            $pay = $allocation[$po->id] ?? null;

            return [
                'id' => $po->id,
                'date' => $po->document_date,
                'supplier_id' => $po->supplier_id,
                'supplier_name' => $po->supplier?->displayName() ?? '—',
                'reference' => $po->legacy_po_no ?? '#'.$po->id,
                'currency' => $po->currency_code,
                'amount' => (float) $po->total_amount,
                'payment_status' => $pay['status'] ?? 'unpaid',
                'payment_label' => $pay['label'] ?? 'غير مدفوعة',
                'line_count' => $po->lines->count(),
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

    /** @return Builder<PurchaseOrder> */
    private function baseQuery(ReportPeriodFilters $filters): Builder
    {
        $query = PurchaseOrder::query()
            ->where('status', 'issued')
            ->whereNull('deleted_at')
            ->whereDate('document_date', '>=', $filters->resolvedDateFrom())
            ->whereDate('document_date', '<=', $filters->resolvedDateTo());

        if ($filters->currency !== null) {
            $query->where('currency_code', $filters->currency);
        }

        if ($filters->supplierId !== null) {
            $query->where('supplier_id', $filters->supplierId);
        }

        return $query;
    }
}
