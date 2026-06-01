<?php

namespace App\Livewire\Concerns;

/**
 * Business Purpose: Keep cashflow filter selects/dates in draft until apply is clicked.
 */
trait UsesCommittedCashflowFilters
{
    public string $filterMethodDraft = '';

    public string $filterCurrencyDraft = '';

    public string $filterDateFromDraft = '';

    public string $filterDateToDraft = '';

    public function mountUsesCommittedCashflowFilters(): void
    {
        $this->syncCashflowFilterDraftsFromApplied();
    }

    protected function syncCashflowFilterDraftsFromApplied(): void
    {
        $this->filterMethodDraft = $this->filterMethod;
        $this->filterCurrencyDraft = $this->filterCurrency;
        $this->filterDateFromDraft = $this->filterDateFrom;
        $this->filterDateToDraft = $this->filterDateTo;
    }

    protected function commitCashflowFilterDrafts(): void
    {
        $this->filterMethod = $this->filterMethodDraft;
        $this->filterCurrency = $this->filterCurrencyDraft;
        $this->filterDateFrom = $this->filterDateFromDraft;
        $this->filterDateTo = $this->filterDateToDraft;
    }

    protected function clearCashflowFilterDrafts(): void
    {
        $this->filterMethodDraft = '';
        $this->filterCurrencyDraft = '';
        $this->filterDateFromDraft = '';
        $this->filterDateToDraft = '';
    }
}
