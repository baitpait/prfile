<?php

namespace App\Services;

use App\Models\Supplier;
use Illuminate\Support\Collection;

class SupplierStatementService
{
    /**
     * كشف التزامات تجاه المورد حسب العملة.
     * الرصيد الموجب = المتبقي المستحق للمورد (أوامر شراء − دفعات).
     *
     * @return array<string, array{
     *   currency: string,
     *   purchase_orders: Collection,
     *   payments: Collection,
     *   total_ordered: float,
     *   total_paid: float,
     *   balance: float
     * }>
     */
    public function forSupplier(Supplier $supplier, ?string $dateFrom = null, ?string $dateTo = null): array
    {
        $ordersQuery = $supplier->purchaseOrders()
            ->with('lines')
            ->whereIn('status', ['issued'])
            ->whereNull('deleted_at');

        $paymentsQuery = $supplier->payments()
            ->whereNull('deleted_at');

        if ($dateFrom) {
            $ordersQuery->where('document_date', '>=', $dateFrom);
            $paymentsQuery->where('paid_at', '>=', $dateFrom);
        }

        if ($dateTo) {
            $ordersQuery->where('document_date', '<=', $dateTo);
            $paymentsQuery->where('paid_at', '<=', $dateTo);
        }

        $orders = $ordersQuery->orderBy('document_date')->orderBy('id')->get();
        $payments = $paymentsQuery->orderBy('paid_at')->orderBy('id')->get();

        $currencies = $orders->pluck('currency_code')
            ->merge($payments->pluck('currency_code'))
            ->unique()
            ->sort()
            ->values();

        $result = [];

        foreach ($currencies as $currency) {
            $currOrders = $orders->where('currency_code', $currency);
            $currPayments = $payments->where('currency_code', $currency);

            $totalOrdered = $currOrders->sum(fn ($po) => (float) $po->total_amount);
            $totalPaid = $currPayments->sum(fn ($pay) => (float) $pay->amount);

            $events = collect();

            foreach ($currOrders as $po) {
                $events->push([
                    'type' => 'purchase_order',
                    'date' => $po->document_date,
                    'sort' => $po->document_date->format('Y-m-d').'_0_'.$po->id,
                    'model' => $po,
                    'amount' => (float) $po->total_amount,
                ]);
            }

            foreach ($currPayments as $pay) {
                $events->push([
                    'type' => 'payment',
                    'date' => $pay->paid_at,
                    'sort' => $pay->paid_at->format('Y-m-d').'_1_'.$pay->id,
                    'model' => $pay,
                    'amount' => (float) $pay->amount,
                ]);
            }

            $events = $events->sortBy('sort')->values();

            $running = 0.0;
            $timeline = [];
            foreach ($events as $event) {
                if ($event['type'] === 'purchase_order') {
                    $running += $event['amount'];
                } else {
                    $running -= $event['amount'];
                }
                $timeline[] = array_merge($event, ['running_balance' => $running]);
            }

            $result[$currency] = [
                'currency' => $currency,
                'purchase_orders' => $currOrders->values(),
                'payments' => $currPayments->values(),
                'total_ordered' => $totalOrdered,
                'total_paid' => $totalPaid,
                'balance' => $totalOrdered - $totalPaid,
                'timeline' => $timeline,
            ];
        }

        return $result;
    }

    /**
     * @return list<list<string|float>>
     */
    public function toCsvRows(array $statement): array
    {
        $rows = [['العملة', 'النوع', 'التاريخ', 'المرجع', 'المبلغ', 'الرصيد التراكمي']];

        foreach ($statement as $currency => $section) {
            $running = 0.0;

            foreach ($section['purchase_orders'] as $po) {
                $running += (float) $po->total_amount;
                $rows[] = [
                    $currency,
                    'أمر شراء',
                    $po->document_date->format('Y-m-d'),
                    $po->legacy_po_no ?? "#{$po->id}",
                    number_format((float) $po->total_amount, 2),
                    number_format($running, 2),
                ];
            }

            foreach ($section['payments'] as $pay) {
                $running -= (float) $pay->amount;
                $rows[] = [
                    $currency,
                    'دفعة',
                    $pay->paid_at->format('Y-m-d'),
                    $pay->bank_reference ?? "#{$pay->id}",
                    number_format((float) $pay->amount, 2),
                    number_format($running, 2),
                ];
            }
        }

        return $rows;
    }
}
