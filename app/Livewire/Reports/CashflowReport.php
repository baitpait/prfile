<?php

namespace App\Livewire\Reports;

use App\Livewire\Concerns\HasPeriodReportFilters;
use App\Services\Reports\CashflowReportService;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CashflowReport extends Component
{
    use HasPeriodReportFilters;

    /** @var list<array<string, mixed>> */
    public array $rows = [];

    /** @var array<string, array{inflow: float, supplier_outflow: float, expense_outflow: float, net: float}> */
    public array $summary = [];

    /** @var list<string> */
    public array $currencyOptions = [];

    public function mount(): void
    {
        $this->mountPeriodReportFilters();
        $this->currencyOptions = (new CashflowReportService)->currencyOptions();
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
        $this->method = '';
        $this->loadReport();
    }

    public function loadReport(): void
    {
        $svc = new CashflowReportService;
        $filters = $this->buildPeriodFilters();
        $this->rows = $svc->timeline($filters)->all();
        $this->summary = $svc->summaryByCurrency($filters);
    }

    public function pdfExportUrl(): string
    {
        return route('reports.cashflow.pdf', $this->periodQueryParams());
    }

    public function exportCsv(): StreamedResponse
    {
        Gate::authorize('export-period-reports');

        $filters = $this->buildPeriodFilters();
        $rows = (new CashflowReportService)->timeline($filters);
        $summary = (new CashflowReportService)->summaryByCurrency($filters);

        $filename = 'تدفق-نقدي-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($rows, $summary, $filters): void {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($handle, ['التاريخ', 'النوع', 'الطرف', 'المرجع', 'طريقة الدفع', 'العملة', 'المبلغ', 'المبلغ الموقّع', 'ملاحظات']);
            foreach ($rows as $r) {
                fputcsv($handle, [
                    $r['date']->format('Y-m-d'),
                    $r['type_label'],
                    $r['party'],
                    $r['reference'],
                    $r['method_label'],
                    $r['currency'],
                    number_format($r['amount'], 2, '.', ''),
                    number_format($r['signed_amount'], 2, '.', ''),
                    $r['notes'] ?? '',
                ]);
            }
            fputcsv($handle, []);
            fputcsv($handle, ['ملخص حسب العملة']);
            fputcsv($handle, ['العملة', 'وارد (عملاء)', 'صادر (موردين)', 'صادر (مصروفات)', 'صافي']);
            foreach ($summary as $cur => $s) {
                fputcsv($handle, [
                    $cur,
                    number_format($s['inflow'], 2, '.', ''),
                    number_format($s['supplier_outflow'], 2, '.', ''),
                    number_format($s['expense_outflow'], 2, '.', ''),
                    number_format($s['net'], 2, '.', ''),
                ]);
            }
            fputcsv($handle, []);
            fputcsv($handle, ['الفترة', $filters->resolvedDateFrom()->format('Y-m-d'), $filters->resolvedDateTo()->format('Y-m-d')]);
            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function render()
    {
        return view('livewire.reports.cashflow-report', [
            'pdfExportUrl' => $this->pdfExportUrl(),
        ]);
    }
}
