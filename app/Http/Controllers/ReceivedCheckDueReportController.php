<?php

namespace App\Http\Controllers;

use App\Models\ReceivedCheck;
use App\Services\ArabicPdfRenderer;
use App\Services\Finance\ReceivedCheckDueReportService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;

class ReceivedCheckDueReportController extends Controller
{
    use AuthorizesRequests;

    public function pdf(Request $request, ArabicPdfRenderer $pdfRenderer)
    {
        $this->authorize('exportDueReport', ReceivedCheck::class);

        $currency = trim((string) $request->query('currency', ''));
        $bucket = trim((string) $request->query('bucket', ''));

        $service = app(ReceivedCheckDueReportService::class);
        $rows = $service->rows(
            $currency !== '' ? $currency : null,
            $bucket !== '' ? $bucket : null,
        );
        $totalsByCurrency = $service->totalsByCurrency($bucket !== '' ? $bucket : null);
        $filterLabels = $service->describeFilters(
            $currency !== '' ? $currency : null,
            $bucket !== '' ? $bucket : null,
        );

        $companyName = config('app.company_display_name', 'Profile Media Production');

        $html = view('pdf.received-check-due-report', [
            'rows' => $rows,
            'totalsByCurrency' => $totalsByCurrency,
            'filterLabels' => $filterLabels,
            'bucketOptions' => $service->bucketLabels(),
            'service' => $service,
            'companyName' => $companyName,
            'printedAt' => now()->format('d/m/Y H:i'),
        ])->render();

        $filename = 'received-checks-due-'.now()->format('Ymd-His').'.pdf';

        return $pdfRenderer->stream(
            $html,
            $filename,
            'inline',
            'تقرير استحقاق الشيكات',
            $companyName,
        );
    }
}
