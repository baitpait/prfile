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

        $orders = $ordersQuery->orderBy('document_date')->get();
        $payments = $paymentsQuery->orderBy('paid_at')->get();

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

            $result[$currency] = [
                'currency' => $currency,
                'purchase_orders' => $currOrders->values(),
                'payments' => $currPayments->values(),
                'total_ordered' => $totalOrdered,
                'total_paid' => $totalPaid,
                'balance' => $totalOrdered - $totalPaid,
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
