<?php

namespace App\Livewire\Reports;

use App\Livewire\Concerns\HasPeriodReportFilters;
use App\Services\Reports\FinancialPeriodSummaryService;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FinancialPeriodSummaryReport extends Component
{
    use HasPeriodReportFilters;

    /** @var array<string, array<string, mixed>> */
    public array $summary = [];

    /** @var list<string> */
    public array $currencyOptions = [];

    public function mount(): void
    {
        $this->mountPeriodReportFilters();
        $this->currencyOptions = (new FinancialPeriodSummaryService)->currencyOptions();
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
        $this->loadReport();
    }

    public function loadReport(): void
    {
        $this->summary = (new FinancialPeriodSummaryService)->byCurrency($this->buildPeriodFilters());
    }

    public function pdfExportUrl(): string
    {
        return route('reports.financial-period.pdf', $this->periodQueryParams());
    }

    public function exportCsv(): StreamedResponse
    {
        Gate::authorize('export-period-reports');

        $filters = $this->buildPeriodFilters();
        $summary = (new FinancialPeriodSummaryService)->byCurrency($filters);

        $filename = 'لوحة-الفترة-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($summary, $filters): void {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($handle, ['العملة', 'مبيعات', 'مشتريات', 'دفعات عملاء', 'دفعات موردين', 'مصروفات', 'صافي نقدي', 'ذمم عملاء', 'التزام موردين', 'عدد فواتير', 'عدد أوامر شراء']);
            foreach ($summary as $cur => $s) {
                fputcsv($handle, [
                    $cur,
                    number_format($s['sales'], 2, '.', ''),
                    number_format($s['purchases'], 2, '.', ''),
                    number_format($s['client_payments'], 2, '.', ''),
                    number_format($s['supplier_payments'], 2, '.', ''),
                    number_format($s['expenses'], 2, '.', ''),
                    number_format($s['net_cash'], 2, '.', ''),
                    number_format($s['client_receivables'] ?? 0, 2, '.', ''),
                    number_format($s['supplier_payables'] ?? 0, 2, '.', ''),
                    (string) $s['invoice_count'],
                    (string) $s['po_count'],
                ]);
            }
            fputcsv($handle, []);
            fputcsv($handle, ['الفترة', $filters->resolvedDateFrom()->format('Y-m-d'), $filters->resolvedDateTo()->format('Y-m-d')]);
            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function render()
    {
        return view('livewire.reports.financial-period-summary-report', [
            'pdfExportUrl' => $this->pdfExportUrl(),
        ]);
    }
}
