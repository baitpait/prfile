<?php

namespace App\Livewire\Reports;

use App\Livewire\Concerns\HasPeriodReportFilters;
use App\Models\Supplier;
use App\Services\Reports\AggregatedSupplierStatementsService;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AggregatedSupplierStatementsReport extends Component
{
    use HasPeriodReportFilters;

    /** @var list<array<string, mixed>> */
    public array $rows = [];

    /** @var array<string, array<string, float|int>> */
    public array $totals = [];

    /** @var list<string> */
    public array $currencyOptions = [];

    /** @var array<int, string> */
    public array $supplierOptions = [];

    public function mount(): void
    {
        $this->mountPeriodReportFilters();
        $this->currencyOptions = (new AggregatedSupplierStatementsService)->currencyOptions();
        $this->supplierOptions = Supplier::query()->whereNull('deleted_at')->orderBy('business_name')->pluck('business_name', 'id')->all();
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
        $this->supplierId = '';
        $this->loadReport();
    }

    public function loadReport(): void
    {
        $svc = new AggregatedSupplierStatementsService;
        $filters = $this->buildPeriodFilters();
        $this->rows = $svc->rows($filters)->all();
        $this->totals = $svc->totalsByCurrency($filters);
    }

    public function pdfExportUrl(): string
    {
        return route('reports.aggregated-supplier-statements.pdf', $this->periodQueryParams());
    }

    public function exportCsv(): StreamedResponse
    {
        Gate::authorize('export-period-reports');

        $filters = $this->buildPeriodFilters();
        $rows = (new AggregatedSupplierStatementsService)->rows($filters);

        $filename = 'كشوف-الموردين-المجمعة-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($rows, $filters): void {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($handle, ['المورد', 'العملة', 'إجمالي أوامر الشراء', 'إجمالي الدفعات', 'إجمالي التسويات', 'المتبقي', 'عدد الحركات']);
            foreach ($rows as $r) {
                fputcsv($handle, [
                    $r['supplier_name'],
                    $r['currency'],
                    number_format($r['total_ordered'], 2, '.', ''),
                    number_format($r['total_paid'], 2, '.', ''),
                    number_format($r['total_adjusted'], 2, '.', ''),
                    number_format($r['balance'], 2, '.', ''),
                    (string) $r['movement_count'],
                ]);
            }
            fputcsv($handle, []);
            fputcsv($handle, ['الفترة', $filters->resolvedDateFrom()->format('Y-m-d'), $filters->resolvedDateTo()->format('Y-m-d')]);
            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function render()
    {
        return view('livewire.reports.aggregated-supplier-statements-report', [
            'pdfExportUrl' => $this->pdfExportUrl(),
        ]);
    }
}
