<?php

namespace App\Services;

use App\Models\Client;
use Illuminate\Support\Collection;

class ClientStatementService
{
    /**
     * Build a multi-currency statement for a client.
     *
     * Returns an array keyed by currency_code:
     *   [
     *     'ILS' => [
     *       'currency' => 'ILS',
     *       'invoices' => Collection,
     *       'payments' => Collection,
     *       'total_invoiced' => float,
     *       'total_paid' => float,
     *       'balance' => float,   // positive = still owed by client
     *     ],
     *     ...
     *   ]
     */
    public function forClient(Client $client, ?string $dateFrom = null, ?string $dateTo = null): array
    {
        $invoicesQuery = $client->invoices()
            ->with('lines')
            ->whereIn('status', ['issued'])
            ->whereNull('deleted_at');

        $paymentsQuery = $client->payments()
            ->whereNull('deleted_at');

        if ($dateFrom) {
            $invoicesQuery->where('document_date', '>=', $dateFrom);
            $paymentsQuery->where('paid_at', '>=', $dateFrom);
        }

        if ($dateTo) {
            $invoicesQuery->where('document_date', '<=', $dateTo);
            $paymentsQuery->where('paid_at', '<=', $dateTo);
        }

        $invoices = $invoicesQuery->orderBy('document_date')->orderBy('id')->get();
        $payments = $paymentsQuery->orderBy('paid_at')->orderBy('id')->get();

        $currencies = $invoices->pluck('currency_code')
            ->merge($payments->pluck('currency_code'))
            ->unique()
            ->sort()
            ->values();

        $result = [];

        foreach ($currencies as $currency) {
            $currInvoices = $invoices->where('currency_code', $currency)->values();
            $currPayments = $payments->where('currency_code', $currency)->values();

            $totalInvoiced = $currInvoices->sum(fn ($inv) => (float) $inv->total_amount);
            $totalPaid     = $currPayments->sum(fn ($pay) => (float) $pay->amount);

            // بناء جدول زمني مرتب بالتاريخ
            $events = collect();

            foreach ($currInvoices as $inv) {
                $events->push([
                    'type'   => 'invoice',
                    'date'   => $inv->document_date,
                    'sort'   => $inv->document_date->format('Y-m-d') . '_0_' . $inv->id,
                    'model'  => $inv,
                    'amount' => (float) $inv->total_amount,
                ]);
            }

            foreach ($currPayments as $pay) {
                $events->push([
                    'type'   => 'payment',
                    'date'   => $pay->paid_at,
                    'sort'   => $pay->paid_at->format('Y-m-d') . '_1_' . $pay->id,
                    'model'  => $pay,
                    'amount' => (float) $pay->amount,
                ]);
            }

            $events = $events->sortBy('sort')->values();

            // احتساب الرصيد المتراكم
            $running = 0.0;
            $timeline = [];
            foreach ($events as $event) {
                if ($event['type'] === 'invoice') {
                    $running += $event['amount'];
                } else {
                    $running -= $event['amount'];
                }
                $timeline[] = array_merge($event, ['running_balance' => $running]);
            }

            $result[$currency] = [
                'currency'       => $currency,
                'invoices'       => $currInvoices,
                'payments'       => $currPayments,
                'total_invoiced' => $totalInvoiced,
                'total_paid'     => $totalPaid,
                'balance'        => $totalInvoiced - $totalPaid,
                'timeline'       => $timeline,
            ];
        }

        return $result;
    }

    public function toCsvRows(array $statement): array
    {
        $rows = [['العملة', 'النوع', 'التاريخ', 'المرجع', 'المبلغ', 'الرصيد التراكمي']];

        foreach ($statement as $currency => $section) {
            $running = 0.0;

            foreach ($section['invoices'] as $inv) {
                $running += (float) $inv->total_amount;
                $rows[] = [
                    $currency,
                    'فاتورة',
                    $inv->document_date->format('Y-m-d'),
                    $inv->legacy_invoice_no ?? "#{$inv->id}",
                    number_format((float) $inv->total_amount, 2),
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
