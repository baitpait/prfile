<?php

namespace App\Services;

use App\Models\ClientPayment;
use App\Models\Invoice;
use Illuminate\Support\Collection;

/**
 * Business Purpose: Derive per-invoice payment status.
 * Linked payments (invoice_id) cover that invoice fully; unlinked payments apply FIFO to the rest.
 * Balance adjustments are excluded — payment status reflects cash/bank collections only.
 */
class InvoicePaymentAllocationService
{
    public const UNPAID = 'unpaid';

    public const PARTIAL = 'partial';

    public const PAID = 'paid';

    /**
     * @return array{allocated: float, remaining: float, total: float, status: string, label: string}|null
     */
    public function forInvoice(Invoice $invoice): ?array
    {
        if ($invoice->status !== 'issued') {
            return null;
        }

        $map = $this->forInvoices(collect([$invoice]));

        return $map[$invoice->id] ?? null;
    }

    /**
     * @param  Collection<int, Invoice>  $invoices
     * @return array<int, array{allocated: float, remaining: float, total: float, status: string, label: string}>
     */
    public function forInvoices(Collection $invoices): array
    {
        $issued = $invoices->filter(fn (Invoice $inv) => $inv->status === 'issued');

        if ($issued->isEmpty()) {
            return [];
        }

        $result = [];

        foreach ($issued->groupBy(fn (Invoice $inv) => $inv->client_id.'|'.($inv->currency_code ?? 'ILS')) as $group) {
            /** @var Invoice $sample */
            $sample = $group->first();
            $clientId = (int) $sample->client_id;
            $currency = $sample->currency_code ?? 'ILS';

            $clientInvoices = Invoice::query()
                ->where('client_id', $clientId)
                ->where('status', 'issued')
                ->where('currency_code', $currency)
                ->whereNull('deleted_at')
                ->orderBy('document_date')
                ->orderBy('id')
                ->get();

            $payments = ClientPayment::query()
                ->where('client_id', $clientId)
                ->where('currency_code', $currency)
                ->whereNull('deleted_at')
                ->get(['id', 'invoice_id', 'amount']);

            $linkedByInvoice = $payments
                ->filter(fn (ClientPayment $p) => $p->invoice_id !== null)
                ->groupBy('invoice_id')
                ->map(fn (Collection $rows) => (float) $rows->sum('amount'));

            $generalPool = (float) $payments
                ->filter(fn (ClientPayment $p) => $p->invoice_id === null)
                ->sum('amount');

            $unlinkedInvoices = $clientInvoices->filter(
                fn (Invoice $inv) => ! $linkedByInvoice->has($inv->id)
            )->values();

            $fifo = $this->allocateFifo($unlinkedInvoices, $generalPool);

            foreach ($group as $invoice) {
                $total = round((float) $invoice->total_amount, 4);

                if ($linkedByInvoice->has($invoice->id)) {
                    $allocated = min($total, round((float) $linkedByInvoice->get($invoice->id), 4));
                    $remaining = round(max(0, $total - $allocated), 4);
                    $status = self::UNPAID;
                    if ($allocated > 0.00001 && $remaining > 0.00001) {
                        $status = self::PARTIAL;
                    } elseif ($remaining <= 0.00001) {
                        $status = self::PAID;
                    }
                    $result[$invoice->id] = [
                        'allocated' => $allocated,
                        'remaining' => $remaining,
                        'total' => $total,
                        'status' => $status,
                        'label' => self::label($status),
                    ];

                    continue;
                }

                if (! isset($fifo[$invoice->id])) {
                    continue;
                }

                $row = $fifo[$invoice->id];
                $result[$invoice->id] = array_merge($row, [
                    'label' => self::label($row['status']),
                ]);
            }
        }

        return $result;
    }

    /**
     * @param  Collection<int, Invoice>  $invoices  ordered oldest first
     * @return array<int, array{allocated: float, remaining: float, total: float, status: string}>
     */
    public function allocateFifo(Collection $invoices, float $paymentPool): array
    {
        $out = [];

        foreach ($invoices as $invoice) {
            $total = (float) $invoice->total_amount;
            $allocated = min($total, max(0.0, $paymentPool));
            $remaining = $total - $allocated;
            $paymentPool -= $allocated;

            $status = self::UNPAID;
            if ($allocated > 0.00001 && $remaining > 0.00001) {
                $status = self::PARTIAL;
            } elseif ($remaining <= 0.00001) {
                $status = self::PAID;
            }

            $out[$invoice->id] = [
                'allocated' => round($allocated, 4),
                'remaining' => round(max(0, $remaining), 4),
                'total' => round($total, 4),
                'status' => $status,
            ];
        }

        return $out;
    }

    public static function label(string $status): string
    {
        return match ($status) {
            self::PARTIAL => 'جزئية',
            self::PAID => 'مدفوعة بالكامل',
            default => 'غير مدفوعة',
        };
    }

    public static function badgeClass(string $status): string
    {
        return match ($status) {
            self::PAID => 'badge-green',
            self::PARTIAL => 'badge-yellow',
            default => 'badge-red',
        };
    }
}
