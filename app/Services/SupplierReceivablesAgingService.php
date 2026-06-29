<?php

namespace App\Services;

use App\Models\PurchaseOrder;
use App\Models\Supplier;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Business Purpose: Supplier payables balance and aging days from oldest unpaid PO (FIFO on payments).
 */
class SupplierReceivablesAgingService
{
    /**
     * @return Collection<int, array{
     *   supplier_id: int,
     *   supplier_name: string,
     *   phone: string|null,
     *   currency_code: string,
     *   balance: float,
     *   days_from_first_unpaid: int,
     *   first_unpaid_document_date: string|null
     * }>
     */
    public function rows(?SupplierReceivablesAgingFilters $filters = null): Collection
    {
        $filters ??= new SupplierReceivablesAgingFilters;
        $statementService = new SupplierStatementService;
        $today = Carbon::now()->startOfDay();
        $out = collect();

        Supplier::query()->whereNull('deleted_at')->orderBy('id')->each(function (Supplier $supplier) use ($statementService, $filters, $today, &$out): void {
            $statement = $statementService->forSupplier($supplier);

            foreach ($statement as $currency => $section) {
                if ($filters->currency !== null && $filters->currency !== '' && $currency !== $filters->currency) {
                    continue;
                }

                $balance = (float) $section['balance'];
                if ($balance <= 0.00001) {
                    continue;
                }

                $fifo = $this->firstUnpaidOrderAfterFifo(
                    $section['purchase_orders'],
                    $section['payments'],
                    $today
                );

                $out->push([
                    'supplier_id' => $supplier->id,
                    'supplier_name' => $supplier->displayName(),
                    'phone' => $this->displayPhone($supplier),
                    'currency_code' => $currency,
                    'balance' => round($balance, 2),
                    'days_from_first_unpaid' => $fifo['days_from_first_unpaid'],
                    'first_unpaid_document_date' => $fifo['first_unpaid_document_date'],
                ]);
            }
        });

        return $this->applyFilters($out, $filters)
            ->sortByDesc('balance')
            ->sortByDesc('days_from_first_unpaid')
            ->values();
    }

    /**
     * @return array{
     *   total_balance: float,
     *   supplier_count: int,
     *   buckets: array{0_30: float, 31_60: float, 61_90: float, 91_plus: float},
     *   cumulative: array{through_30: float, through_60: float, through_90: float, all: float}
     * }
     */
    public function summary(?SupplierReceivablesAgingFilters $filters = null): array
    {
        $rows = $this->rows($filters);

        $buckets = [
            '0_30' => 0.0,
            '31_60' => 0.0,
            '61_90' => 0.0,
            '91_plus' => 0.0,
        ];

        foreach ($rows as $row) {
            $days = (int) $row['days_from_first_unpaid'];
            $amount = (float) $row['balance'];

            if ($days <= 30) {
                $buckets['0_30'] += $amount;
            } elseif ($days <= 60) {
                $buckets['31_60'] += $amount;
            } elseif ($days <= 90) {
                $buckets['61_90'] += $amount;
            } else {
                $buckets['91_plus'] += $amount;
            }
        }

        foreach ($buckets as $key => $value) {
            $buckets[$key] = round($value, 2);
        }

        $through30 = $buckets['0_30'];
        $through60 = $through30 + $buckets['31_60'];
        $through90 = $through60 + $buckets['61_90'];
        $all = $through90 + $buckets['91_plus'];

        return [
            'total_balance' => round($rows->sum(fn (array $r): float => (float) $r['balance']), 2),
            'supplier_count' => $rows->count(),
            'buckets' => $buckets,
            'cumulative' => [
                'through_30' => round($through30, 2),
                'through_60' => round($through60, 2),
                'through_90' => round($through90, 2),
                'all' => round($all, 2),
            ],
        ];
    }

    /** @return list<string> */
    public function currenciesWithPayables(): array
    {
        $statementService = new SupplierStatementService;
        $set = [];

        Supplier::query()->whereNull('deleted_at')->each(function (Supplier $supplier) use ($statementService, &$set): void {
            foreach ($statementService->forSupplier($supplier) as $currency => $section) {
                if ((float) $section['balance'] > 0.00001) {
                    $set[$currency] = true;
                }
            }
        });

        return collect(array_keys($set))->sort()->values()->all();
    }

    /** @param Collection<int, array<string, mixed>> $rows */
    private function applyFilters(Collection $rows, SupplierReceivablesAgingFilters $filters): Collection
    {
        if (! $filters->hasAny()) {
            return $rows;
        }

        [$daysMin, $daysMax] = $this->resolveDayBounds($filters);
        $search = $filters->search !== null && $filters->search !== ''
            ? mb_strtolower(trim($filters->search))
            : null;

        return $rows->filter(function (array $row) use ($filters, $daysMin, $daysMax, $search): bool {
            $days = (int) $row['days_from_first_unpaid'];

            if ($daysMin !== null && $days < $daysMin) {
                return false;
            }

            if ($daysMax !== null && $days > $daysMax) {
                return false;
            }

            if ($filters->minBalance !== null && (float) $row['balance'] < $filters->minBalance) {
                return false;
            }

            if ($search !== null) {
                $name = mb_strtolower((string) $row['supplier_name']);
                $phone = mb_strtolower((string) ($row['phone'] ?? ''));

                if (! str_contains($name, $search) && ! str_contains($phone, $search)) {
                    return false;
                }
            }

            return true;
        })->values();
    }

    /** @return array{0: int|null, 1: int|null} */
    private function resolveDayBounds(SupplierReceivablesAgingFilters $filters): array
    {
        if ($filters->agingBucket !== null && $filters->agingBucket !== '') {
            return match ($filters->agingBucket) {
                '0_30' => [0, 30],
                '31_60' => [31, 60],
                '61_90' => [61, 90],
                '91_plus' => [91, null],
                default => [$filters->daysMin, $filters->daysMax],
            };
        }

        return [$filters->daysMin, $filters->daysMax];
    }

    /**
     * @param  Collection<int, PurchaseOrder>  $orders
     * @param  Collection<int, \App\Models\SupplierPayment>  $payments
     * @return array{days_from_first_unpaid: int, first_unpaid_document_date: string|null}
     */
    private function firstUnpaidOrderAfterFifo(Collection $orders, Collection $payments, Carbon $today): array
    {
        $paymentPool = (float) $payments->sum(fn ($pay) => (float) $pay->amount);

        $sorted = $orders
            ->sortBy(fn (PurchaseOrder $po) => $po->document_date->format('Y-m-d').'_'.$po->id)
            ->values();

        $firstUnpaid = null;

        foreach ($sorted as $order) {
            $orderTotal = (float) $order->total_amount;
            $allocated = min($orderTotal, max(0.0, $paymentPool));
            $remaining = $orderTotal - $allocated;
            $paymentPool -= $allocated;

            if ($remaining > 0.00001 && $firstUnpaid === null) {
                $firstUnpaid = $order;
            }
        }

        if ($firstUnpaid === null) {
            $firstUnpaid = $sorted->first();
        }

        if ($firstUnpaid === null) {
            return [
                'days_from_first_unpaid' => 0,
                'first_unpaid_document_date' => null,
            ];
        }

        $start = $firstUnpaid->document_date->copy()->startOfDay();

        return [
            'days_from_first_unpaid' => (int) $start->diffInDays($today),
            'first_unpaid_document_date' => $firstUnpaid->document_date->toDateString(),
        ];
    }

    private function displayPhone(Supplier $supplier): ?string
    {
        $primary = trim((string) ($supplier->phone_primary ?? ''));
        if ($primary !== '') {
            return $primary;
        }

        $secondary = trim((string) ($supplier->phone_secondary ?? ''));

        return $secondary !== '' ? $secondary : null;
    }
}
