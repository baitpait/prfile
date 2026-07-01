<?php

namespace App\Http\Controllers;

use App\Models\ClientPayment;
use App\Services\Documents\PaymentVoucherService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class ClientPaymentPrintController extends Controller
{
    use AuthorizesRequests;

    public function __construct(private PaymentVoucherService $vouchers) {}

    /**
     * Business Purpose: Printable receipt voucher (سند قبض) for a client payment with company letterhead.
     */
    public function show(ClientPayment $payment)
    {
        $this->authorize('view', $payment);

        return view('payments.voucher-print', $this->vouchers->forClientPayment($payment));
    }
}
