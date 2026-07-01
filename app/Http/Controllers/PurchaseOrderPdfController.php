<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\StreamsDocumentPdf;
use App\Models\PurchaseOrder;
use App\Services\Documents\PurchaseOrderDocumentService;
use App\Services\Documents\PrintViewPdfRenderer;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class PurchaseOrderPdfController extends Controller
{
    use AuthorizesRequests;
    use StreamsDocumentPdf;

    public function __construct(private PurchaseOrderDocumentService $documents) {}

    public function show(PurchaseOrder $purchaseOrder, PrintViewPdfRenderer $pdfRenderer)
    {
        $this->authorize('view', $purchaseOrder);

        $ref = $purchaseOrder->legacy_po_no ?? 'po-'.$purchaseOrder->id;
        $filename = 'purchase-order-'.preg_replace('/[^A-Za-z0-9._-]+/', '-', $ref).'.pdf';

        return $this->streamPrintViewPdf(
            $pdfRenderer,
            'purchase-orders.print',
            $this->documents->viewData($purchaseOrder),
            $filename,
        );
    }
}
