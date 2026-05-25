<?php

namespace App\Services;

use App\Models\Supplier;

class SupplierStatementService
{
    /**
     * كشف التزامات تجاه المورد حسب العملة.
     * الرصيد = أوامر شراء − دفعات − تسويات.
     */
    public function forSupplier(Supplier $supplier, ?string $dateFrom = null, ?string $dateTo = null): array
    {
        $ordersQuery = $supplier->purchaseOrders()
            ->with('lines')
            ->whereIn('status', ['issued'])
            ->whereNull('deleted_at');

        $paymentsQuery = $supplier->payments()
            ->whereNull('deleted_at');

        $adjustmentsQuery = $supplier->balanceAdjustments()
            ->whereNull('deleted_at');

        if ($dateFrom) {
            $ordersQuery->where('document_date', '>=', $dateFrom);
            $paymentsQuery->where('paid_at', '>=', $dateFrom);
            $adjustmentsQuery->where('adjustment_date', '>=', $dateFrom);
        }

        if ($dateTo) {
            $ordersQuery->where('document_date', '<=', $dateTo);
            $paymentsQuery->where('paid_at', '<=', $dateTo);
            $adjustmentsQuery->where('adjustment_date', '<=', $dateTo);
        }

        $orders = $ordersQuery->orderBy('document_date')->orderBy('id')->get();
        $payments = $paymentsQuery->orderBy('paid_at')->orderBy('id')->get();
        $adjustments = $adjustmentsQuery->orderBy('adjustment_date')->orderBy('id')->get();

        $currencies = $orders->pluck('currency_code')
            ->merge($payments->pluck('currency_code'))
            ->merge($adjustments->pluck('currency_code'))
            ->unique()
            ->sort()
            ->values();

        $result = [];

        foreach ($currencies as $currency) {
            $currOrders = $orders->where('currency_code', $currency)->values();
            $currPayments = $payments->where('currency_code', $currency)->values();
            $currAdjustments = $adjustments->where('currency_code', $currency)->values();

            $totalOrdered = $currOrders->sum(fn ($po) => (float) $po->total_amount);
            $totalPaid = $currPayments->sum(fn ($pay) => (float) $pay->amount);
            $totalAdjusted = $currAdjustments->sum(fn ($adj) => (float) $adj->amount);

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

            foreach ($currAdjustments as $adj) {
                $events->push([
                    'type' => 'adjustment',
                    'date' => $adj->adjustment_date,
                    'sort' => $adj->adjustment_date->format('Y-m-d').'_2_'.$adj->id,
                    'model' => $adj,
                    'amount' => (float) $adj->amount,
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
                'purchase_orders' => $currOrders,
                'payments' => $currPayments,
                'adjustments' => $currAdjustments,
                'total_ordered' => $totalOrdered,
                'total_paid' => $totalPaid,
                'total_adjusted' => $totalAdjusted,
                'balance' => $totalOrdered - $totalPaid - $totalAdjusted,
                'timeline' => $timeline,
            ];
        }

        return $result;
    }

    /**
     * @return list<list<string>>
     */
    public function toCsvRows(array $statement): array
    {
        $rows = [['العملة', 'التاريخ', 'البيان', 'المبلغ']];

        foreach ($statement as $currency => $section) {
            foreach ($section['timeline'] ?? [] as $event) {
                if ($event['type'] === 'purchase_order') {
                    $po = $event['model'];
                    $ref = $po->legacy_po_no ?? "#{$po->id}";
                    $rows[] = [
                        $currency,
                        $event['date']->format('Y-m-d'),
                        "أمر شراء {$ref}",
                        '+'.number_format((float) $event['amount'], 2, '.', ''),
                    ];
                } elseif ($event['type'] === 'payment') {
                    $pay = $event['model'];
                    $ref = $pay->bank_reference ?? "#{$pay->id}";
                    $rows[] = [
                        $currency,
                        $event['date']->format('Y-m-d'),
                        "دفعة {$ref}",
                        '-'.number_format((float) $event['amount'], 2, '.', ''),
                    ];
                } else {
                    $adj = $event['model'];
                    $rows[] = [
                        $currency,
                        $event['date']->format('Y-m-d'),
                        'تسوية #'.$adj->id.' ('.$adj->typeLabel().')',
                        '-'.number_format((float) $event['amount'], 2, '.', ''),
                    ];
                }
            }

            $rows[] = [$currency, '', 'إجمالي أوامر الشراء', number_format((float) $section['total_ordered'], 2, '.', '')];
            $rows[] = [$currency, '', 'إجمالي الدفعات', number_format((float) $section['total_paid'], 2, '.', '')];
            $rows[] = [$currency, '', 'إجمالي التسويات', number_format((float) $section['total_adjusted'], 2, '.', '')];
            $rows[] = [$currency, '', 'المتبقي للمورد', number_format((float) $section['balance'], 2, '.', '')];
            $rows[] = ['', '', '', ''];
        }

        return $rows;
    }
}
