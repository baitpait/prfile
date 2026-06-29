<?php

namespace App\Services\Reports;

use App\Models\ClientPayment;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\SupplierPayment;

/**
 * Business Purpose: Period financial KPIs per currency for executive dashboard.
 */
class FinancialPeriodSummaryService
{
    /**
     * @return array<string, array{
     *   sales: float,
     *   purchases: float,
     *   client_payments: float,
     *   supplier_payments: float,
     *   expenses: float,
     *   net_cash: float,
     *   invoice_count: int,
     *   po_count: int
     * }>
     */
    public function byCurrency(ReportPeriodFilters $filters): array
    {
        $from = $filters->resolvedDateFrom();
        $to = $filters->resolvedDateTo();

        $sales = Invoice::query()
            ->where('status', 'issued')
            ->whereNull('deleted_at')
            ->whereDate('document_date', '>=', $from)
            ->whereDate('document_date', '<=', $to)
            ->when($filters->currency, fn ($q) => $q->where('currency_code', $filters->currency))
            ->get(['currency_code', 'total_amount']);

        $purchases = PurchaseOrder::query()
            ->where('status', 'issued')
            ->whereNull('deleted_at')
            ->whereDate('document_date', '>=', $from)
            ->whereDate('document_date', '<=', $to)
            ->when($filters->currency, fn ($q) => $q->where('currency_code', $filters->currency))
            ->get(['currency_code', 'total_amount']);

        $clientPayments = ClientPayment::query()
            ->whereNull('deleted_at')
            ->whereDate('paid_at', '>=', $from)
            ->whereDate('paid_at', '<=', $to)
            ->when($filters->currency, fn ($q) => $q->where('currency_code', $filters->currency))
            ->get(['currency_code', 'amount']);

        $supplierPayments = SupplierPayment::query()
            ->whereNull('deleted_at')
            ->whereDate('paid_at', '>=', $from)
            ->whereDate('paid_at', '<=', $to)
            ->when($filters->currency, fn ($q) => $q->where('currency_code', $filters->currency))
            ->get(['currency_code', 'amount']);

        $expenses = Expense::query()
            ->whereNull('deleted_at')
            ->whereDate('expense_date', '>=', $from)
            ->whereDate('expense_date', '<=', $to)
            ->when($filters->currency, fn ($q) => $q->where('currency_code', $filters->currency))
            ->get(['currency_code', 'amount']);

        $currencies = collect()
            ->merge($sales->pluck('currency_code'))
            ->merge($purchases->pluck('currency_code'))
            ->merge($clientPayments->pluck('currency_code'))
            ->merge($supplierPayments->pluck('currency_code'))
            ->merge($expenses->pluck('currency_code'))
            ->unique()
            ->sort()
            ->values();

        if ($filters->currency !== null) {
            $currencies = collect([$filters->currency]);
        }

        $result = [];

        foreach ($currencies as $cur) {
            $salesTotal = (float) $sales->where('currency_code', $cur)->sum('total_amount');
            $purchaseTotal = (float) $purchases->where('currency_code', $cur)->sum('total_amount');
            $clientPaid = (float) $clientPayments->where('currency_code', $cur)->sum('amount');
            $supplierPaid = (float) $supplierPayments->where('currency_code', $cur)->sum('amount');
            $expenseTotal = (float) $expenses->where('currency_code', $cur)->sum('amount');

            $result[$cur] = [
                'sales' => round($salesTotal, 2),
                'purchases' => round($purchaseTotal, 2),
                'client_payments' => round($clientPaid, 2),
                'supplier_payments' => round($supplierPaid, 2),
                'expenses' => round($expenseTotal, 2),
                'net_cash' => round($clientPaid - $supplierPaid - $expenseTotal, 2),
                'invoice_count' => $sales->where('currency_code', $cur)->count(),
                'po_count' => $purchases->where('currency_code', $cur)->count(),
            ];
        }

        $dateTo = $to->format('Y-m-d');
        $clientBalances = (new ClientReceivablesSummaryService)->aggregateBalancesAsOf($dateTo, $filters->currency);
        $supplierBalances = (new SupplierPayablesSummaryService)->aggregateBalancesAsOf($dateTo, $filters->currency);

        foreach ($clientBalances as $cur => $amount) {
            if (! isset($result[$cur])) {
                $result[$cur] = $this->emptyCurrencyRow();
            }
            $result[$cur]['client_receivables'] = round($amount, 2);
        }

        foreach ($supplierBalances as $cur => $amount) {
            if (! isset($result[$cur])) {
                $result[$cur] = $this->emptyCurrencyRow();
            }
            $result[$cur]['supplier_payables'] = round($amount, 2);
        }

        foreach ($result as $cur => $row) {
            $result[$cur]['client_receivables'] = $result[$cur]['client_receivables'] ?? 0.0;
            $result[$cur]['supplier_payables'] = $result[$cur]['supplier_payables'] ?? 0.0;
        }

        return $result;
    }

    /** @return array<string, mixed> */
    private function emptyCurrencyRow(): array
    {
        return [
            'sales' => 0.0,
            'purchases' => 0.0,
            'client_payments' => 0.0,
            'supplier_payments' => 0.0,
            'expenses' => 0.0,
            'net_cash' => 0.0,
            'invoice_count' => 0,
            'po_count' => 0,
            'client_receivables' => 0.0,
            'supplier_payables' => 0.0,
        ];
    }

    /** @return list<string> */
    public function currencyOptions(): array
    {
        return Product::billingCurrencies();
    }
}

