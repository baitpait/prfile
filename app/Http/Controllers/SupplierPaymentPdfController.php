<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\StreamsDocumentPdf;
use App\Models\SupplierPayment;
use App\Services\Documents\PaymentVoucherService;
use App\Services\Documents\PrintViewPdfRenderer;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class SupplierPaymentPdfController extends Controller
{
    use AuthorizesRequests;
    use StreamsDocumentPdf;

    public function __construct(private PaymentVoucherService $vouchers) {}

    public function show(SupplierPayment $supplierPayment, PrintViewPdfRenderer $pdfRenderer)
    {
        $this->authorize('view', $supplierPayment);

        return $this->streamPrintViewPdf(
            $pdfRenderer,
            'payments.voucher-print',
            $this->vouchers->forSupplierPayment($supplierPayment),
            'supplier-payment-'.$supplierPayment->id.'.pdf',
        );
    }
}
