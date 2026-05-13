<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\View\View;

class ClientReceivablesAgingController extends Controller
{
    use AuthorizesRequests;

    public function __invoke(): View
    {
        $this->authorize('view-client-receivables-aging');

        return view('reports.client-receivables-aging');
    }
}
