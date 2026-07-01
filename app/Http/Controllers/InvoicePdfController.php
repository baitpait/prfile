<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\StreamsDocumentPdf;
use App\Models\Invoice;
use App\Services\Documents\InvoiceDocumentService;
use App\Services\Documents\PrintViewPdfRenderer;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class InvoicePdfController extends Controller
{
    use AuthorizesRequests;
    use StreamsDocumentPdf;

    public function __construct(private InvoiceDocumentService $documents) {}

    public function show(Invoice $invoice, PrintViewPdfRenderer $pdfRenderer)
    {
        $this->authorize('view', $invoice);

        $ref = $invoice->legacy_invoice_no ?? 'invoice-'.$invoice->id;
        $filename = 'invoice-'.preg_replace('/[^A-Za-z0-9._-]+/', '-', $ref).'.pdf';

        return $this->streamPrintViewPdf(
            $pdfRenderer,
            'invoices.print',
            $this->documents->viewData($invoice),
            $filename,
        );
    }
}
