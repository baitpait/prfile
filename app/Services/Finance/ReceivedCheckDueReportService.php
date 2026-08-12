<?php

namespace App\Services\Finance;

use App\Models\Product;
use App\Models\ReceivedCheck;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Business Purpose: Due-date maturity report for pending checks — liquidity planning.
 */
class ReceivedCheckDueReportService
{
    public const BUCKET_OVERDUE = 'overdue';

    public const BUCKET_DUE_0_7 = 'due_0_7';

    public const BUCKET_DUE_8_30 = 'due_8_30';

    public const BUCKET_DUE_31_PLUS = 'due_31_plus';

    /**
     * @return list<string>
     */
    public function supportedCurrencies(): array
    {
        return Product::billingCurrencies();
    }

    /**
     * @return array<string, string>
     */
    public function bucketLabels(): array
    {
        return [
            self::BUCKET_OVERDUE => 'متأخر',
            self::BUCKET_DUE_0_7 => 'خلال 7 أيام',
            self::BUCKET_DUE_8_30 => '8–30 يوماً',
            self::BUCKET_DUE_31_PLUS => 'أكثر من 30 يوماً',
        ];
    }

    /**
     * @return Collection<int, ReceivedCheck>
     */
    public function rows(?string $currencyCode = null, ?string $bucket = null): Collection
    {
        $today = now()->startOfDay();

        return ReceivedCheck::query()
            ->with('client')
            ->where('status', ReceivedCheckStatus::PENDING)
            ->whereNull('deleted_at')
            ->when($currencyCode !== null && $currencyCode !== '', fn ($q) => $q->where('currency_code', $currencyCode))
            ->when($bucket !== null && $bucket !== '', fn ($q) => $this->applyBucketFilter($q, $bucket, $today))
            ->orderBy('due_date')
            ->orderBy('id')
            ->get();
    }

    /**
     * @return array<string, array{count: int, total: float}>
     */
    public function totalsByCurrency(?string $bucket = null): array
    {
        $rows = $this->rows(null, $bucket);
        $totals = [];

        foreach ($rows as $row) {
            $code = $row->currency_code ?? 'ILS';
            if (! isset($totals[$code])) {
                $totals[$code] = ['count' => 0, 'total' => 0.0];
            }
            $totals[$code]['count']++;
            $totals[$code]['total'] += round((float) $row->amount, 2);
        }

        foreach ($totals as $code => $box) {
            $totals[$code]['total'] = round($box['total'], 2);
        }

        ksort($totals);

        return $totals;
    }

    /**
     * @return array<string, int>
     */
    public function countsByBucket(?string $currencyCode = null): array
    {
        $counts = [];
        foreach (array_keys($this->bucketLabels()) as $bucket) {
            $counts[$bucket] = $this->rows($currencyCode, $bucket)->count();
        }

        return $counts;
    }

    public function daysUntilDue(ReceivedCheck $check, ?Carbon $today = null): int
    {
        $today = ($today ?? now())->startOfDay();
        $due = $check->due_date?->copy()->startOfDay();
        if ($due === null) {
            return 0;
        }

        return (int) $today->diffInDays($due, false);
    }

    public function bucketForCheck(ReceivedCheck $check, ?Carbon $today = null): string
    {
        $days = $this->daysUntilDue($check, $today);

        if ($days < 0) {
            return self::BUCKET_OVERDUE;
        }
        if ($days <= 7) {
            return self::BUCKET_DUE_0_7;
        }
        if ($days <= 30) {
            return self::BUCKET_DUE_8_30;
        }

        return self::BUCKET_DUE_31_PLUS;
    }

    /**
     * @return list<string>
     */
    public function describeFilters(?string $currencyCode = null, ?string $bucket = null): array
    {
        $labels = [];
        if ($currencyCode !== null && $currencyCode !== '') {
            $labels[] = 'العملة: '.$currencyCode;
        }
        if ($bucket !== null && $bucket !== '') {
            $labels[] = 'الفترة: '.($this->bucketLabels()[$bucket] ?? $bucket);
        }

        return $labels;
    }

    protected function applyBucketFilter($query, string $bucket, Carbon $today): void
    {
        $in7 = $today->copy()->addDays(7)->endOfDay();
        $in30 = $today->copy()->addDays(30)->endOfDay();

        match ($bucket) {
            self::BUCKET_OVERDUE => $query->whereDate('due_date', '<', $today),
            self::BUCKET_DUE_0_7 => $query
                ->whereDate('due_date', '>=', $today)
                ->whereDate('due_date', '<=', $in7),
            self::BUCKET_DUE_8_30 => $query
                ->whereDate('due_date', '>', $in7)
                ->whereDate('due_date', '<=', $in30),
            self::BUCKET_DUE_31_PLUS => $query->whereDate('due_date', '>', $in30),
            default => null,
        };
    }
}
