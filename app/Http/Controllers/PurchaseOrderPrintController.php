<?php

namespace App\Http\Controllers;

use App\Models\PurchaseOrder;
use App\Services\Documents\PurchaseOrderDocumentService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class PurchaseOrderPrintController extends Controller
{
    use AuthorizesRequests;

    public function __construct(private PurchaseOrderDocumentService $documents) {}

    public function show(PurchaseOrder $purchaseOrder)
    {
        $this->authorize('view', $purchaseOrder);

        return view('purchase-orders.print', $this->documents->viewData($purchaseOrder));
    }
}
