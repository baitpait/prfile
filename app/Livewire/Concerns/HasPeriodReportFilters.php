<?php

namespace App\Livewire\Concerns;

use App\Services\Reports\ReportPeriodFilters;
use Livewire\Attributes\Url;

/**
 * Business Purpose: Shared period filters (date range, currency, method) for financial reports.
 */
trait HasPeriodReportFilters
{
    #[Url(as: 'date_from')]
    public string $dateFrom = '';

    #[Url(as: 'date_to')]
    public string $dateTo = '';

    #[Url]
    public string $currency = '';

    #[Url]
    public string $method = '';

    #[Url(as: 'client_id')]
    public string $clientId = '';

    #[Url(as: 'supplier_id')]
    public string $supplierId = '';

    public function mountPeriodReportFilters(): void
    {
        if ($this->dateFrom === '') {
            $this->dateFrom = now()->startOfMonth()->format('Y-m-d');
        }

        if ($this->dateTo === '') {
            $this->dateTo = now()->format('Y-m-d');
        }
    }

    public function applyPeriodFilters(): void
    {
        // Subclasses reload data in overridden method or via hook.
    }

    public function clearPeriodFilters(): void
    {
        $this->dateFrom = now()->startOfMonth()->format('Y-m-d');
        $this->dateTo = now()->format('Y-m-d');
        $this->currency = '';
        $this->method = '';
        $this->clientId = '';
        $this->supplierId = '';
    }

    public function hasActivePeriodFilters(): bool
    {
        return $this->currency !== ''
            || $this->method !== ''
            || $this->clientId !== ''
            || $this->supplierId !== '';
    }

    protected function buildPeriodFilters(): ReportPeriodFilters
    {
        return ReportPeriodFilters::fromArray([
            'date_from' => $this->dateFrom,
            'date_to' => $this->dateTo,
            'currency' => $this->currency,
            'method' => $this->method,
            'client_id' => $this->clientId,
            'supplier_id' => $this->supplierId,
        ]);
    }

    /** @return array<string, string> */
    protected function periodQueryParams(): array
    {
        return $this->buildPeriodFilters()->queryParams();
    }
}
