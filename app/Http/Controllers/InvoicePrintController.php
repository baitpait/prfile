<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Services\Documents\InvoiceDocumentService;

class InvoicePrintController extends Controller
{
    public function __construct(private InvoiceDocumentService $documents) {}

    public function show(Invoice $invoice)
    {
        return view('invoices.print', $this->documents->viewData($invoice));
    }
}
