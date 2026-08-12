<?php

namespace App\Livewire;

use App\Models\ReceivedCheck;
use App\Services\Finance\ReceivedCheckDueReportService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Url;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReceivedCheckDueReport extends Component
{
    use AuthorizesRequests;

    #[Url]
    public string $currency = '';

    #[Url]
    public string $bucket = '';

    public function mount(): void
    {
        $this->authorize('viewAny', ReceivedCheck::class);
    }

    public function pdfExportUrl(): string
    {
        return route('received-checks.due-report.pdf', array_filter([
            'currency' => $this->currency !== '' ? $this->currency : null,
            'bucket' => $this->bucket !== '' ? $this->bucket : null,
        ]));
    }

    public function exportCsv(): StreamedResponse
    {
        $this->authorize('exportDueReport', ReceivedCheck::class);

        $service = app(ReceivedCheckDueReportService::class);
        $rows = $service->rows(
            $this->currency !== '' ? $this->currency : null,
            $this->bucket !== '' ? $this->bucket : null,
        );
        $bucketOptions = $service->bucketLabels();

        $filename = 'استحقاق-الشيكات-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($rows, $service, $bucketOptions): void {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($handle, ['العميل', 'رقم الشيك', 'الاستحقاق', 'أيام', 'الفترة', 'المبلغ', 'العملة']);
            foreach ($rows as $row) {
                $days = $service->daysUntilDue($row);
                $bucketCode = $service->bucketForCheck($row);
                fputcsv($handle, [
                    $row->client?->displayName() ?? '—',
                    $row->check_number,
                    $row->due_date?->format('Y-m-d') ?? '',
                    $days,
                    $bucketOptions[$bucketCode] ?? '',
                    number_format((float) $row->amount, 2, '.', ''),
                    $row->currency_code,
                ]);
            }
            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function render()
    {
        $service = app(ReceivedCheckDueReportService::class);

        return view('livewire.received-check-due-report', [
            'rows' => $service->rows(
                $this->currency !== '' ? $this->currency : null,
                $this->bucket !== '' ? $this->bucket : null,
            ),
            'totalsByCurrency' => $service->totalsByCurrency(
                $this->bucket !== '' ? $this->bucket : null,
            ),
            'countsByBucket' => $service->countsByBucket(
                $this->currency !== '' ? $this->currency : null,
            ),
            'currencyOptions' => $service->supportedCurrencies(),
            'bucketOptions' => $service->bucketLabels(),
            'service' => $service,
            'pdfExportUrl' => $this->pdfExportUrl(),
        ]);
    }
}
