<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use App\Services\SupplierStatementService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Mpdf\Config\ConfigVariables;
use Mpdf\Config\FontVariables;
use Mpdf\Mpdf;

class SupplierStatementController extends Controller
{
    use AuthorizesRequests;

    public function __construct(private SupplierStatementService $service) {}

    public function show(Supplier $supplier)
    {
        $this->authorize('viewStatement', $supplier);

        return view('suppliers.statement', ['supplier' => $supplier]);
    }

    public function pdf(Supplier $supplier, Request $request)
    {
        $this->authorize('exportStatement', $supplier);

        $dateFrom = $request->query('date_from');
        $dateTo = $request->query('date_to');

        $statement = $this->service->forSupplier($supplier, $dateFrom, $dateTo);

        $html = view('pdf.supplier-statement', compact('supplier', 'statement', 'dateFrom', 'dateTo'))->render();

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

        $mpdfTempDir = storage_path('app/mpdf');
        if (! is_dir($mpdfTempDir)) {
            mkdir($mpdfTempDir, 0775, true);
        }

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
            'tempDir' => $mpdfTempDir,
        ]);

        $mpdf->SetDirectionality('rtl');
        $mpdf->autoScriptToLang = true;
        $mpdf->autoLangToFont = true;

        $mpdf->WriteHTML($html);

        $filename = 'supplier-statement-'.$supplier->id.'-'.now()->format('Ymd').'.pdf';

        return response($mpdf->Output($filename, 'S'), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$filename.'"',
        ]);
    }
}
