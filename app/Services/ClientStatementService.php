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

    /**
     * Business Purpose: Export movements in chronological order plus period totals (invoices − payments).
     */
    public function toCsvRows(array $statement): array
    {
        $rows = [['العملة', 'التاريخ', 'البيان', 'المبلغ']];

        foreach ($statement as $currency => $section) {
            foreach ($section['timeline'] ?? [] as $event) {
                if ($event['type'] === 'invoice') {
                    $inv = $event['model'];
                    $ref = $inv->legacy_invoice_no ?? "#{$inv->id}";
                    $rows[] = [
                        $currency,
                        $event['date']->format('Y-m-d'),
                        "فاتورة {$ref}",
                        '+'.number_format((float) $event['amount'], 2, '.', ''),
                    ];
                } else {
                    $pay = $event['model'];
                    $ref = $pay->bank_reference ?? "#{$pay->id}";
                    $rows[] = [
                        $currency,
                        $event['date']->format('Y-m-d'),
                        "دفعة {$ref}",
                        '-'.number_format((float) $event['amount'], 2, '.', ''),
                    ];
                }
            }

            $rows[] = [$currency, '', 'إجمالي الفواتير', number_format((float) $section['total_invoiced'], 2, '.', '')];
            $rows[] = [$currency, '', 'إجمالي الدفعات', number_format((float) $section['total_paid'], 2, '.', '')];
            $rows[] = [$currency, '', 'الرصيد المستحق (فواتير − دفعات)', number_format((float) $section['balance'], 2, '.', '')];
            $rows[] = ['', '', '', ''];
        }

        return $rows;
    }
}
