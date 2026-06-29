<?php

namespace App\Livewire\Reports;

use App\Livewire\Concerns\HasPeriodReportFilters;
use App\Models\Client;
use App\Services\Reports\AsOfSummaryFilters;
use App\Services\Reports\ClientReceivablesSummaryService;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Url;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ClientReceivablesSummaryReport extends Component
{
    use HasPeriodReportFilters;

    #[Url]
    public string $search = '';

    #[Url]
    public string $minBalance = '';

    /** @var list<array<string, mixed>> */
    public array $rows = [];

    /** @var array<string, float> */
    public array $totals = [];

    /** @var list<string> */
    public array $currencyOptions = [];

    /** @var array<int, string> */
    public array $clientOptions = [];

    public function mount(): void
    {
        $this->mountPeriodReportFilters();
        $this->currencyOptions = (new ClientReceivablesSummaryService)->currencyOptions();
        $this->clientOptions = Client::query()->whereNull('deleted_at')->orderBy('business_name')->pluck('business_name', 'id')->all();
        $this->loadReport();
    }

    public function applyPeriodFilters(): void
    {
        $this->loadReport();
    }

    public function clearPeriodFilters(): void
    {
        $this->dateFrom = now()->startOfMonth()->format('Y-m-d');
        $this->dateTo = now()->format('Y-m-d');
        $this->currency = '';
        $this->clientId = '';
        $this->search = '';
        $this->minBalance = '';
        $this->loadReport();
    }

    public function loadReport(): void
    {
        $svc = new ClientReceivablesSummaryService;
        $filters = $this->buildAsOfFilters();
        $this->rows = $svc->rows($filters)->all();
        $this->totals = $svc->totalsByCurrency($filters);
    }

    public function pdfExportUrl(): string
    {
        return route('reports.client-receivables-summary.pdf', $this->asOfQueryParams());
    }

    public function exportCsv(): StreamedResponse
    {
        Gate::authorize('export-period-reports');

        $filters = $this->buildAsOfFilters();
        $rows = (new ClientReceivablesSummaryService)->rows($filters);

        $filename = 'ملخص-ذمم-العملاء-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($rows, $filters): void {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($handle, ['العميل', 'الهاتف', 'العملة', 'إجمالي الفواتير', 'إجمالي الدفعات', 'إجمالي التسويات', 'الرصيد المستحق']);
            foreach ($rows as $r) {
                fputcsv($handle, [
                    $r['client_name'],
                    $r['phone'] ?? '',
                    $r['currency'],
                    number_format($r['total_invoiced'], 2, '.', ''),
                    number_format($r['total_paid'], 2, '.', ''),
                    number_format($r['total_adjusted'], 2, '.', ''),
                    number_format($r['balance'], 2, '.', ''),
                ]);
            }
            fputcsv($handle, []);
            fputcsv($handle, ['حتى تاريخ', $filters->asOfDate->format('Y-m-d')]);
            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function render()
    {
        return view('livewire.reports.client-receivables-summary-report', [
            'pdfExportUrl' => $this->pdfExportUrl(),
        ]);
    }

    private function buildAsOfFilters(): AsOfSummaryFilters
    {
        $min = $this->minBalance !== '' ? (float) $this->minBalance : null;

        return AsOfSummaryFilters::fromPeriodFilters(
            $this->buildPeriodFilters(),
            $this->search,
            $min,
        );
    }

    /** @return array<string, string> */
    private function asOfQueryParams(): array
    {
        return array_filter(array_merge($this->periodQueryParams(), [
            'search' => trim($this->search) !== '' ? trim($this->search) : null,
            'min_balance' => $this->minBalance !== '' ? $this->minBalance : null,
        ]), fn (?string $v): bool => $v !== null && $v !== '');
    }
}
