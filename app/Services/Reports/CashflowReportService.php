<?php

namespace App\Services\Reports;

use App\Models\ClientPayment;
use App\Models\Expense;
use App\Models\Product;
use App\Models\SupplierPayment;
use Illuminate\Support\Collection;

/**
 * Business Purpose: Period cash movements — client inflows minus supplier payments and expenses.
 */
class CashflowReportService
{
    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function timeline(ReportPeriodFilters $filters): Collection
    {
        $events = collect();

        foreach ($this->clientPayments($filters) as $payment) {
            $events->push([
                'sort' => $payment->paid_at->format('Y-m-d H:i:s').'_0_'.$payment->id,
                'type' => 'client_payment',
                'type_label' => 'دفعة عميل',
                'date' => $payment->paid_at,
                'party' => $payment->client?->displayName() ?? '—',
                'reference' => $payment->bank_reference ?? '#'.$payment->id,
                'method' => $payment->method,
                'method_label' => PaymentMethodLabels::label($payment->method),
                'currency' => $payment->currency_code,
                'amount' => (float) $payment->amount,
                'signed_amount' => (float) $payment->amount,
                'notes' => $payment->notes,
            ]);
        }

        foreach ($this->supplierPayments($filters) as $payment) {
            $events->push([
                'sort' => $payment->paid_at->format('Y-m-d H:i:s').'_1_'.$payment->id,
                'type' => 'supplier_payment',
                'type_label' => 'دفعة مورد',
                'date' => $payment->paid_at,
                'party' => $payment->supplier?->displayName() ?? '—',
                'reference' => $payment->bank_reference ?? '#'.$payment->id,
                'method' => $payment->method,
                'method_label' => PaymentMethodLabels::label($payment->method),
                'currency' => $payment->currency_code,
                'amount' => (float) $payment->amount,
                'signed_amount' => -1 * (float) $payment->amount,
                'notes' => $payment->notes,
            ]);
        }

        foreach ($this->expenses($filters) as $expense) {
            $events->push([
                'sort' => $expense->expense_date->format('Y-m-d').'_2_'.$expense->id,
                'type' => 'expense',
                'type_label' => 'مصروف',
                'date' => $expense->expense_date,
                'party' => $expense->description,
                'reference' => '#'.$expense->id,
                'method' => null,
                'method_label' => '—',
                'currency' => $expense->currency_code,
                'amount' => (float) $expense->amount,
                'signed_amount' => -1 * (float) $expense->amount,
                'notes' => $expense->notes,
            ]);
        }

        return $events->sortBy('sort')->values();
    }

    /**
     * @return array<string, array{inflow: float, supplier_outflow: float, expense_outflow: float, net: float}>
     */
    public function summaryByCurrency(ReportPeriodFilters $filters): array
    {
        $summary = [];

        foreach ($this->timeline($filters) as $event) {
            $cur = $event['currency'];
            if (! isset($summary[$cur])) {
                $summary[$cur] = [
                    'inflow' => 0.0,
                    'supplier_outflow' => 0.0,
                    'expense_outflow' => 0.0,
                    'net' => 0.0,
                ];
            }

            if ($event['type'] === 'client_payment') {
                $summary[$cur]['inflow'] += $event['amount'];
            } elseif ($event['type'] === 'supplier_payment') {
                $summary[$cur]['supplier_outflow'] += $event['amount'];
            } else {
                $summary[$cur]['expense_outflow'] += $event['amount'];
            }

            $summary[$cur]['net'] += $event['signed_amount'];
        }

        ksort($summary);

        return $summary;
    }

    /**
     * @return Collection<int, ClientPayment>
     */
    private function clientPayments(ReportPeriodFilters $filters): Collection
    {
        $query = ClientPayment::query()
            ->with('client')
            ->whereNull('deleted_at')
            ->whereDate('paid_at', '>=', $filters->resolvedDateFrom())
            ->whereDate('paid_at', '<=', $filters->resolvedDateTo());

        if ($filters->currency !== null) {
            $query->where('currency_code', $filters->currency);
        }

        if ($filters->method !== null) {
            $query->where('method', $filters->method);
        }

        if ($filters->clientId !== null) {
            $query->where('client_id', $filters->clientId);
        }

        return $query->orderBy('paid_at')->orderBy('id')->get();
    }

    /**
     * @return Collection<int, SupplierPayment>
     */
    private function supplierPayments(ReportPeriodFilters $filters): Collection
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

        return $query->orderBy('paid_at')->orderBy('id')->get();
    }

    /**
     * @return Collection<int, Expense>
     */
    private function expenses(ReportPeriodFilters $filters): Collection
    {
        $query = Expense::query()
            ->whereNull('deleted_at')
            ->whereDate('expense_date', '>=', $filters->resolvedDateFrom())
            ->whereDate('expense_date', '<=', $filters->resolvedDateTo());

        if ($filters->currency !== null) {
            $query->where('currency_code', $filters->currency);
        }

        return $query->orderBy('expense_date')->orderBy('id')->get();
    }

    /** @return list<string> */
    public function currencyOptions(): array
    {
        return Product::billingCurrencies();
    }
}
