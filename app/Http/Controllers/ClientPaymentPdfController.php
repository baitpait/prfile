<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\StreamsDocumentPdf;
use App\Models\ClientPayment;
use App\Services\Documents\PaymentVoucherService;
use App\Services\Documents\PrintViewPdfRenderer;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class ClientPaymentPdfController extends Controller
{
    use AuthorizesRequests;
    use StreamsDocumentPdf;

    public function __construct(private PaymentVoucherService $vouchers) {}

    public function show(ClientPayment $payment, PrintViewPdfRenderer $pdfRenderer)
    {
        $this->authorize('view', $payment);

        return $this->streamPrintViewPdf(
            $pdfRenderer,
            'payments.voucher-print',
            $this->vouchers->forClientPayment($payment),
            'client-payment-'.$payment->id.'.pdf',
        );
    }
}
