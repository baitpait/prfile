<?php

namespace App\Services;

use Illuminate\Http\Request;

/**
 * معايير تصفية تقرير أعمار ذمم العملاء (تُمرَّر من Livewire وتُطبَّق على الصفوف والملخص).
 */
readonly class ClientReceivablesAgingFilters
{
    public function __construct(
        public ?string $currency = null,
        public ?string $agingBucket = null,
        public ?int $daysMin = null,
        public ?int $daysMax = null,
        public ?float $minBalance = null,
        public ?string $search = null,
    ) {}

    public function hasAny(): bool
    {
        return $this->currency !== null
            || $this->agingBucket !== null
            || $this->daysMin !== null
            || $this->daysMax !== null
            || $this->minBalance !== null
            || ($this->search !== null && $this->search !== '');
    }

    public static function fromRequest(Request $request): self
    {
        $currency = trim((string) $request->query('currency', ''));
        $agingBucket = trim((string) $request->query('agingBucket', ''));
        $daysMin = trim((string) $request->query('daysMin', ''));
        $daysMax = trim((string) $request->query('daysMax', ''));
        $minBalance = trim((string) $request->query('minBalance', ''));
        $search = trim((string) $request->query('search', ''));

        return new self(
            currency: $currency !== '' ? $currency : null,
            agingBucket: $agingBucket !== '' ? $agingBucket : null,
            daysMin: $daysMin !== '' ? max(0, (int) $daysMin) : null,
            daysMax: $daysMax !== '' ? max(0, (int) $daysMax) : null,
            minBalance: $minBalance !== '' ? max(0, (float) $minBalance) : null,
            search: $search !== '' ? $search : null,
        );
    }

    /**
     * @return list<string>
     */
    public function describeActive(): array
    {
        $labels = [];

        if ($this->currency !== null) {
            $labels[] = 'العملة: '.$this->currency;
        }

        if ($this->agingBucket !== null) {
            $labels[] = 'فئة التأخير: '.match ($this->agingBucket) {
                '0_30' => '0–30 يوم',
                '31_60' => '31–60 يوم',
                '61_90' => '61–90 يوم',
                '91_plus' => '91+ يوم',
                default => $this->agingBucket,
            };
        }

        if ($this->daysMin !== null) {
            $labels[] = 'أيام من: '.$this->daysMin;
        }

        if ($this->daysMax !== null) {
            $labels[] = 'أيام إلى: '.$this->daysMax;
        }

        if ($this->minBalance !== null) {
            $labels[] = 'حد أدنى للمبلغ: '.number_format($this->minBalance, 2);
        }

        if ($this->search !== null && $this->search !== '') {
            $labels[] = 'بحث: '.$this->search;
        }

        return $labels;
    }
}
