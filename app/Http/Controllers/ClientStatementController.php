<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Services\ClientStatementService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class ClientStatementController extends Controller
{
    public function __construct(private ClientStatementService $service) {}

    public function show(Client $client)
    {
        return view('clients.statement', ['client' => $client]);
    }

    public function pdf(Client $client, Request $request)
    {
        $dateFrom = $request->query('date_from');
        $dateTo   = $request->query('date_to');

        $statement = $this->service->forClient($client, $dateFrom, $dateTo);

        $pdf = Pdf::loadView('pdf.client-statement', compact('client', 'statement', 'dateFrom', 'dateTo'))
            ->setPaper('a4')
            ->setOption('isRemoteEnabled', false)
            ->setOption('defaultFont', 'DejaVu Sans');

        $filename = 'client-statement-' . $client->id . '-' . now()->format('Ymd') . '.pdf';

        return $pdf->stream($filename);
    }
}
