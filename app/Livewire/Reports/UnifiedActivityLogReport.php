<?php

namespace App\Livewire\Reports;

use App\Livewire\Concerns\HasPeriodReportFilters;
use App\Models\Client;
use App\Models\Supplier;
use App\Services\Reports\UnifiedActivityLogService;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

class UnifiedActivityLogReport extends Component
{
    use HasPeriodReportFilters;

    /** @var list<array<string, mixed>> */
    public array $rows = [];

    public bool $truncated = false;

    public int $totalCount = 0;

    /** @var list<string> */
    public array $currencyOptions = [];

    /** @var array<int, string> */
    public array $clientOptions = [];

    /** @var array<int, string> */
    public array $supplierOptions = [];

    public function mount(): void
    {
        $this->mountPeriodReportFilters();
        $this->currencyOptions = (new UnifiedActivityLogService)->currencyOptions();
        $this->clientOptions = Client::query()->whereNull('deleted_at')->orderBy('business_name')->pluck('business_name', 'id')->all();
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
        $this->method = '';
        $this->clientId = '';
        $this->supplierId = '';
        $this->loadReport();
    }

    public function loadReport(): void
    {
        $result = (new UnifiedActivityLogService)->timeline($this->buildPeriodFilters());
        $this->rows = $result['rows']->all();
        $this->truncated = $result['truncated'];
        $this->totalCount = $result['total'];
    }

    public function pdfExportUrl(): string
    {
        return route('reports.activity-log.pdf', $this->periodQueryParams());
    }

    public function exportCsv(): StreamedResponse
    {
        Gate::authorize('export-period-reports');

        $filters = $this->buildPeriodFilters();
        $result = (new UnifiedActivityLogService)->timeline($filters, UnifiedActivityLogService::MAX_ROWS);

        $filename = 'سجل-النشاط-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($result, $filters): void {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($handle, ['التاريخ', 'النوع', 'الطرف', 'المرجع', 'العملة', 'المبلغ', 'المبلغ الموقّع']);
            foreach ($result['rows'] as $r) {
                fputcsv($handle, [
                    $r['date']->format('Y-m-d'),
                    $r['type_label'],
                    $r['party'],
                    $r['reference'],
                    $r['currency'],
                    number_format($r['amount'], 2, '.', ''),
                    number_format($r['signed_amount'], 2, '.', ''),
                ]);
            }
            if ($result['truncated']) {
                fputcsv($handle, []);
                fputcsv($handle, ['تنبيه', 'تم اقتصار التصدير على '.UnifiedActivityLogService::MAX_ROWS.' سجل من '.$result['total']]);
            }
            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function render()
    {
        return view('livewire.reports.unified-activity-log-report', [
            'pdfExportUrl' => $this->pdfExportUrl(),
        ]);
    }
}
