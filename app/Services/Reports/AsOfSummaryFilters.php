<?php

namespace App\Services\Reports;

use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * Business Purpose: Filters for point-in-time receivables/payables summary (R08, R13).
 */
readonly class AsOfSummaryFilters
{
    public function __construct(
        public Carbon $asOfDate,
        public ?string $currency = null,
        public ?int $clientId = null,
        public ?int $supplierId = null,
        public ?string $search = null,
        public ?float $minBalance = null,
    ) {}

    public static function fromPeriodFilters(ReportPeriodFilters $period, ?string $search = null, ?float $minBalance = null): self
    {
        return new self(
            asOfDate: $period->resolvedDateTo(),
            currency: $period->currency,
            clientId: $period->clientId,
            supplierId: $period->supplierId,
            search: $search !== null && trim($search) !== '' ? trim($search) : null,
            minBalance: $minBalance,
        );
    }

    public static function fromRequest(Request $request): self
    {
        $period = ReportPeriodFilters::fromRequest($request);
        $search = trim((string) ($request->input('search') ?? ''));
        $minRaw = $request->input('min_balance');
        $minBalance = $minRaw !== null && $minRaw !== '' ? (float) $minRaw : null;

        return self::fromPeriodFilters(
            $period,
            $search !== '' ? $search : null,
            $minBalance,
        );
    }

    /** @return list<string> */
    public function describeActive(): array
    {
        $labels = ['حتى تاريخ: '.$this->asOfDate->format('d/m/Y')];

        if ($this->currency !== null) {
            $labels[] = 'العملة: '.$this->currency;
        }

        if ($this->clientId !== null) {
            $labels[] = 'عميل #'.$this->clientId;
        }

        if ($this->supplierId !== null) {
            $labels[] = 'مورد #'.$this->supplierId;
        }

        if ($this->search !== null) {
            $labels[] = 'بحث: '.$this->search;
        }

        if ($this->minBalance !== null) {
            $labels[] = 'حد أدنى: '.number_format($this->minBalance, 2);
        }

        return $labels;
    }
}
