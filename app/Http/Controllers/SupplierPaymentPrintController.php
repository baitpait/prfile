<?php

namespace App\Http\Controllers;

use App\Models\SupplierPayment;
use App\Services\Documents\PaymentVoucherService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class SupplierPaymentPrintController extends Controller
{
    use AuthorizesRequests;

    public function __construct(private PaymentVoucherService $vouchers) {}

    public function show(SupplierPayment $supplierPayment)
    {
        $this->authorize('view', $supplierPayment);

        return view('payments.voucher-print', $this->vouchers->forSupplierPayment($supplierPayment));
    }
}
