<?php

namespace App\Livewire\Concerns;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

/**
 * Business Purpose: Shared currency, payment method, and date-range filters for payment/expense lists.
 */
trait FiltersCashflowList
{
    use UsesCommittedCashflowFilters;

    #[\Livewire\Attributes\Url(as: 'cf_method')]
    public string $filterMethod = '';

    #[\Livewire\Attributes\Url(as: 'cf_cur')]
    public string $filterCurrency = '';

    #[\Livewire\Attributes\Url(as: 'cf_from')]
    public string $filterDateFrom = '';

    #[\Livewire\Attributes\Url(as: 'cf_to')]
    public string $filterDateTo = '';

    public function applyListFilters(): void
    {
        $this->commitCashflowFilterDrafts();
        $this->resetPage();
    }

    protected function resetCashflowFilters(): void
    {
        $this->filterMethod = '';
        $this->filterCurrency = '';
        $this->filterDateFrom = '';
        $this->filterDateTo = '';
        $this->clearCashflowFilterDrafts();
    }

    /**
     * @param  Builder<\Illuminate\Database\Eloquent\Model>  $query
     * @param  list<string>  $validCurrencies
     */
    protected function applyCashflowCurrencyFilter(Builder $query, array $validCurrencies, string $column = 'currency_code'): Builder
    {
        $filterCur = strtoupper(trim($this->filterCurrency));
        if ($filterCur !== '' && in_array($filterCur, $validCurrencies, true)) {
            $query->where($column, $filterCur);
        }

        return $query;
    }

    /**
     * @param  Builder<\Illuminate\Database\Eloquent\Model>  $query
     * @param  list<string>  $validMethods
     */
    protected function applyCashflowMethodFilter(Builder $query, array $validMethods = ['cash', 'bank', 'check', 'transfer']): Builder
    {
        if (in_array($this->filterMethod, $validMethods, true)) {
            $query->where('method', $this->filterMethod);
        }

        return $query;
    }

    /**
     * @param  Builder<\Illuminate\Database\Eloquent\Model>  $query
     */
    protected function applyCashflowDateFilters(Builder $query, string $dateColumn): Builder
    {
        if ($this->filterDateFrom !== '') {
            try {
                $from = Carbon::parse($this->filterDateFrom)->startOfDay();
                $query->whereDate($dateColumn, '>=', $from);
            } catch (\Throwable) {
            }
        }

        if ($this->filterDateTo !== '') {
            try {
                $to = Carbon::parse($this->filterDateTo)->endOfDay();
                $query->whereDate($dateColumn, '<=', $to);
            } catch (\Throwable) {
            }
        }

        return $query;
    }

    public function hasActiveCashflowFilters(): bool
    {
        return $this->filterMethod !== ''
            || $this->filterCurrency !== ''
            || $this->filterDateFrom !== ''
            || $this->filterDateTo !== '';
    }
}
