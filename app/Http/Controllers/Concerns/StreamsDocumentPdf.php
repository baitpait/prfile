<?php

namespace App\Http\Controllers\Concerns;

use App\Services\Documents\PrintViewPdfRenderer;
use Symfony\Component\HttpFoundation\Response;

trait StreamsDocumentPdf
{
    /** @param array<string, mixed> $data */
    protected function streamPrintViewPdf(
        PrintViewPdfRenderer $pdfRenderer,
        string $printView,
        array $data,
        string $filename,
    ): Response {
        return $pdfRenderer->streamFromView($printView, $data, $filename);
    }
}
