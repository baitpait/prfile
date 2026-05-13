<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Services\ClientStatementService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Mpdf\Config\ConfigVariables;
use Mpdf\Config\FontVariables;
use Mpdf\Mpdf;

class ClientStatementController extends Controller
{
    use AuthorizesRequests;

    public function __construct(private ClientStatementService $service) {}

    public function show(Client $client)
    {
        $this->authorize('viewStatement', $client);

        return view('clients.statement', ['client' => $client]);
    }

    public function pdf(Client $client, Request $request)
    {
        $this->authorize('exportStatement', $client);

        $dateFrom = $request->query('date_from');
        $dateTo = $request->query('date_to');

        $statement = $this->service->forClient($client, $dateFrom, $dateTo);

        $html = view('pdf.client-statement', compact('client', 'statement', 'dateFrom', 'dateTo'))->render();

        $defaultConfig = (new ConfigVariables)->getDefaults();
        $fontDirs = $defaultConfig['fontDir'];

        $defaultFontConfig = (new FontVariables)->getDefaults();
        $fontData = $defaultFontConfig['fontdata'];

        $mpdfFontDir = base_path('vendor/mpdf/mpdf/ttfonts');

        $fontData = array_merge($fontData, [
            'xbriyaz' => [
                'R' => 'XB Riyaz.ttf',
                'B' => 'XB RiyazBd.ttf',
                'I' => 'XB RiyazIt.ttf',
                'BI' => 'XB RiyazBdIt.ttf',
                'useOTL' => 0xFF,
                'useKashida' => 75,
            ],
        ]);

        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'orientation' => 'P',
            'margin_top' => 20,
            'margin_bottom' => 20,
            'margin_left' => 15,
            'margin_right' => 15,
            'fontDir' => array_merge($fontDirs, [$mpdfFontDir]),
            'fontdata' => $fontData,
            'default_font' => 'xbriyaz',
            'autoScriptToLang' => true,
            'autoLangToFont' => true,
            'useSubstitutions' => true,
        ]);

        $mpdf->SetDirectionality('rtl');
        $mpdf->autoScriptToLang = true;
        $mpdf->autoLangToFont = true;

        $mpdf->WriteHTML($html);

        $filename = 'client-statement-'.$client->id.'-'.now()->format('Ymd').'.pdf';

        return response($mpdf->Output($filename, 'S'), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$filename.'"',
        ]);
    }
}
