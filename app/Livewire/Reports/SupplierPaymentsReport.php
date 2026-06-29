<?php

namespace App\Livewire\Reports;

use App\Livewire\Concerns\HasPeriodReportFilters;
use App\Services\Reports\SupplierPaymentsReportService;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SupplierPaymentsReport extends Component
{
    use HasPeriodReportFilters;

    /** @var list<array<string, mixed>> */
    public array $rows = [];

    /** @var array<string, float> */
    public array $totals = [];

    /** @var list<string> */
    public array $currencyOptions = [];

    public function mount(): void
    {
        $this->mountPeriodReportFilters();
        $this->currencyOptions = (new SupplierPaymentsReportService)->currencyOptions();
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
        $svc = new SupplierPaymentsReportService;
        $filters = $this->buildPeriodFilters();
        $this->rows = $svc->rows($filters)->all();
        $this->totals = $svc->totalsByCurrency($filters);
    }

    public function pdfExportUrl(): string
    {
        return route('reports.supplier-payments.pdf', $this->periodQueryParams());
    }

    public function exportCsv(): StreamedResponse
    {
        Gate::authorize('export-period-reports');

        $filters = $this->buildPeriodFilters();
        $rows = (new SupplierPaymentsReportService)->rows($filters);

        $filename = 'دفعات-الموردين-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($rows, $filters): void {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($handle, ['التاريخ', 'المورد', 'المرجع', 'طريقة الدفع', 'العملة', 'المبلغ', 'ملاحظات']);
            foreach ($rows as $r) {
                fputcsv($handle, [
                    $r['date']->format('Y-m-d'),
                    $r['supplier_name'],
                    $r['reference'],
                    $r['method_label'],
                    $r['currency'],
                    number_format($r['amount'], 2, '.', ''),
                    $r['notes'] ?? '',
                ]);
            }
            fputcsv($handle, []);
            fputcsv($handle, ['الفترة', $filters->resolvedDateFrom()->format('Y-m-d'), $filters->resolvedDateTo()->format('Y-m-d')]);
            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function render()
    {
        return view('livewire.reports.supplier-payments-report', [
            'pdfExportUrl' => $this->pdfExportUrl(),
        ]);
    }
}
