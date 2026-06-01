<?php

namespace App\Livewire\Concerns;

/**
 * Business Purpose: Payment/supplier-payment list filter drafts until apply is clicked.
 */
trait UsesCommittedPaymentListFilters
{
    public string $searchDraft = '';

    public string $clientSearchDraft = '';

    public string $filterClientIdDraft = '';

    public string $supplierSearchDraft = '';

    public string $filterSupplierIdDraft = '';

    public function mountUsesCommittedPaymentListFilters(): void
    {
        $this->syncPaymentListFilterDrafts();
    }

    protected function syncPaymentListFilterDrafts(): void
    {
        $this->searchDraft = $this->search;
        $this->clientSearchDraft = property_exists($this, 'clientSearch') ? $this->clientSearch : '';
        $this->filterClientIdDraft = property_exists($this, 'filterClientId') ? $this->filterClientId : '';
        $this->supplierSearchDraft = property_exists($this, 'supplierSearch') ? $this->supplierSearch : '';
        $this->filterSupplierIdDraft = property_exists($this, 'filterSupplierId') ? $this->filterSupplierId : '';
        $this->syncCashflowFilterDraftsFromApplied();
    }

    protected function commitPaymentListFilterDrafts(): void
    {
        $this->search = trim($this->searchDraft);
        if (property_exists($this, 'clientSearch')) {
            $this->clientSearch = trim($this->clientSearchDraft);
        }
        if (property_exists($this, 'filterClientId')) {
            $this->filterClientId = $this->filterClientIdDraft;
        }
        if (property_exists($this, 'supplierSearch')) {
            $this->supplierSearch = trim($this->supplierSearchDraft);
        }
        if (property_exists($this, 'filterSupplierId')) {
            $this->filterSupplierId = $this->filterSupplierIdDraft;
        }
        $this->commitCashflowFilterDrafts();
    }
}
