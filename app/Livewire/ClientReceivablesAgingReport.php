<?php

namespace App\Livewire;

use App\Services\ClientReceivablesAgingService;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Url;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ClientReceivablesAgingReport extends Component
{
    #[Url]
    public string $currency = '';

    /** @var list<array<string, mixed>> */
    public array $rows = [];

    /** @var list<string> */
    public array $currencyOptions = [];

    public function mount(): void
    {
        $this->refreshCurrencyOptions();
        $this->loadRows();
    }

    public function updatedCurrency(): void
    {
        $this->refreshCurrencyOptions();
        $this->loadRows();
    }

    protected function refreshCurrencyOptions(): void
    {
        $this->currencyOptions = (new ClientReceivablesAgingService)->currenciesWithReceivables();
    }

    public function loadRows(): void
    {
        $svc = new ClientReceivablesAgingService;
        $filter = $this->currency !== '' ? $this->currency : null;
        $this->rows = $svc->rows($filter)->values()->all();
    }

    public function exportCsv(): StreamedResponse
    {
        Gate::authorize('export-client-receivables-aging-csv');

        $svc = new ClientReceivablesAgingService;
        $filter = $this->currency !== '' ? $this->currency : null;
        $rows = $svc->rows($filter);

        $filename = 'أعمار-ذمم-العملاء-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($rows): void {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($handle, [
                'معرّف العميل',
                'اسم العميل',
                'العملة',
                'معرّف الفاتورة',
                'رقم الفاتورة',
                'تاريخ المستند',
                'تاريخ الاستحقاق',
                'المبلغ',
                'أيام منذ المستند',
                'أيام التأخير',
            ]);
            foreach ($rows as $r) {
                fputcsv($handle, [
                    $r['client_id'],
                    $r['client_name'],
                    $r['currency_code'],
                    $r['invoice_id'],
                    $r['legacy_invoice_no'] ?? '',
                    $r['document_date'],
                    $r['due_date'] ?? '',
                    number_format((float) $r['total_amount'], 2, '.', ''),
                    $r['days_since_document'],
                    $r['days_overdue'] === null ? '' : (string) $r['days_overdue'],
                ]);
            }
            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function render()
    {
        return view('livewire.client-receivables-aging-report');
    }
}
