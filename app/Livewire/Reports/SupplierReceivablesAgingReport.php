<?php

namespace App\Livewire\Reports;

use App\Livewire\Concerns\AppliesListFiltersOnAction;
use App\Services\SupplierReceivablesAgingFilters;
use App\Services\SupplierReceivablesAgingService;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Url;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SupplierReceivablesAgingReport extends Component
{
    use AppliesListFiltersOnAction;

    #[Url]
    public string $currency = '';

    #[Url]
    public string $agingBucket = '';

    #[Url]
    public string $daysMin = '';

    #[Url]
    public string $daysMax = '';

    #[Url]
    public string $minBalance = '';

    #[Url]
    public string $search = '';

    /** @var list<array<string, mixed>> */
    public array $rows = [];

    /** @var array<string, mixed> */
    public array $summary = [
        'total_balance' => 0.0,
        'supplier_count' => 0,
        'buckets' => ['0_30' => 0.0, '31_60' => 0.0, '61_90' => 0.0, '91_plus' => 0.0],
        'cumulative' => ['through_30' => 0.0, 'through_60' => 0.0, 'through_90' => 0.0, 'all' => 0.0],
    ];

    /** @var list<string> */
    public array $currencyOptions = [];

    public function mount(): void
    {
        $this->refreshCurrencyOptions();
        $this->loadRows();
    }

    public function applyReportFilters(): void
    {
        if ($this->agingBucket !== '') {
            $this->daysMin = '';
            $this->daysMax = '';
        }

        if ($this->daysMin !== '' || $this->daysMax !== '') {
            $this->agingBucket = '';
        }

        $this->refreshCurrencyOptions();
        $this->loadRows();
    }

    public function hasActiveReportFilters(): bool
    {
        return $this->currency !== ''
            || $this->agingBucket !== ''
            || $this->daysMin !== ''
            || $this->daysMax !== ''
            || $this->minBalance !== ''
            || trim($this->search) !== '';
    }

    public function clearFilters(): void
    {
        $this->currency = '';
        $this->agingBucket = '';
        $this->daysMin = '';
        $this->daysMax = '';
        $this->minBalance = '';
        $this->search = '';
        $this->refreshCurrencyOptions();
        $this->loadRows();
    }

    protected function refreshCurrencyOptions(): void
    {
        $this->currencyOptions = (new SupplierReceivablesAgingService)->currenciesWithPayables();
    }

    protected function buildFilters(): SupplierReceivablesAgingFilters
    {
        return new SupplierReceivablesAgingFilters(
            currency: $this->currency !== '' ? $this->currency : null,
            agingBucket: $this->agingBucket !== '' ? $this->agingBucket : null,
            daysMin: $this->daysMin !== '' ? max(0, (int) $this->daysMin) : null,
            daysMax: $this->daysMax !== '' ? max(0, (int) $this->daysMax) : null,
            minBalance: $this->minBalance !== '' ? max(0, (float) $this->minBalance) : null,
            search: trim($this->search) !== '' ? trim($this->search) : null,
        );
    }

    public function loadRows(): void
    {
        $svc = new SupplierReceivablesAgingService;
        $filters = $this->buildFilters();
        $this->rows = $svc->rows($filters)->values()->all();
        $this->summary = $svc->summary($filters);
    }

    public function pdfExportUrl(): string
    {
        return route('reports.supplier-receivables-aging.pdf', array_filter([
            'currency' => $this->currency,
            'agingBucket' => $this->agingBucket,
            'daysMin' => $this->daysMin,
            'daysMax' => $this->daysMax,
            'minBalance' => $this->minBalance,
            'search' => $this->search,
        ], fn (string $value): bool => $value !== ''));
    }

    public function exportCsv(): StreamedResponse
    {
        Gate::authorize('export-period-reports');

        $svc = new SupplierReceivablesAgingService;
        $filters = $this->buildFilters();
        $rows = $svc->rows($filters);
        $summary = $svc->summary($filters);

        $filename = 'أعمار-ذمم-الموردين-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($rows, $summary): void {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($handle, [
                'معرّف المورد',
                'اسم المورد',
                'الهاتف',
                'العملة',
                'المبلغ المستحق',
                'أيام من أول أمر غير مسدّد',
                'تاريخ أول ذمة',
            ]);
            foreach ($rows as $r) {
                fputcsv($handle, [
                    $r['supplier_id'],
                    $r['supplier_name'],
                    $r['phone'] ?? '',
                    $r['currency_code'],
                    number_format((float) $r['balance'], 2, '.', ''),
                    $r['days_from_first_unpaid'],
                    $r['first_unpaid_document_date'] ?? '',
                ]);
            }

            fputcsv($handle, []);
            fputcsv($handle, ['ملخص', 'عدد الموردين', (string) $summary['supplier_count']]);
            fputcsv($handle, ['', 'إجمالي الذمم', number_format((float) $summary['total_balance'], 2, '.', '')]);

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function render()
    {
        return view('livewire.reports.supplier-receivables-aging-report', [
            'pdfExportUrl' => $this->pdfExportUrl(),
        ]);
    }
}
