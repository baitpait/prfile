<?php

namespace App\Services\Documents;

use Illuminate\Support\Facades\Process;
use RuntimeException;
use Spatie\Browsershot\Browsershot;
use Symfony\Component\HttpFoundation\Response;

/**
 * Renders the same Blade print templates as the browser print preview, then captures
 * them with headless Chrome (emulateMedia=print) for a pixel-perfect PDF match.
 */
class PrintViewPdfRenderer
{
    public function isAvailable(): bool
    {
        $node = config('browsershot.node_binary', 'node');

        return $this->commandExists($node) && $this->resolveChromePath() !== null;
    }

    /** @param array<string, mixed> $data */
    public function streamFromView(string $view, array $data, string $filename): Response
    {
        set_time_limit((int) config('browsershot.php_timeout', 120));

        $html = view($view, array_merge($data, ['exportMode' => 'pdf']))->render();
        $html = $this->inlineLocalPublicAssets($html);

        try {
            $pdf = $this->configureShot(Browsershot::html($html))->pdf();
        } catch (\Throwable $e) {
            throw new RuntimeException(
                'تعذّر إنشاء PDF من قالب الطباعة. تأكد من تثبيت Node و Puppeteer على السيرفر.',
                previous: $e
            );
        }

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$filename.'"',
        ]);
    }

    /**
     * Headless Chrome must not HTTP-fetch assets from the same php artisan serve worker
     * (single-threaded) or the PDF request deadlocks until max_execution_time.
     */
    private function inlineLocalPublicAssets(string $html): string
    {
        $logoPath = public_path('branding/logo.png');
        if (! is_readable($logoPath)) {
            return $html;
        }

        $dataUri = 'data:image/png;base64,'.base64_encode((string) file_get_contents($logoPath));

        $replaced = preg_replace(
            '#src="[^"]*branding/logo\.png(?:\?[^"]*)?"#',
            'src="'.$dataUri.'"',
            $html
        );

        return is_string($replaced) ? $replaced : $html;
    }

    private function configureShot(Browsershot $shot): Browsershot
    {
        $shot
            ->showBackground()
            ->emulateMedia('print')
            ->format('A4')
            ->margins(0, 0, 0, 0)
            ->setDelay(config('browsershot.pdf_delay_ms', 1500))
            ->setNodeBinary(config('browsershot.node_binary', 'node'))
            ->setNodeModulePath(config('browsershot.node_modules_path', base_path('node_modules')));

        $chrome = $this->resolveChromePath();
        if ($chrome !== null) {
            $shot->setChromePath($chrome);
        }

        if (config('browsershot.no_sandbox', false)) {
            $shot->noSandbox();
        }

        return $shot;
    }

    private function resolveChromePath(): ?string
    {
        $configured = config('browsershot.chrome_path');
        if (is_string($configured) && $configured !== '' && is_executable($configured)) {
            return $configured;
        }

        $node = config('browsershot.node_binary', 'node');
        $modules = config('browsershot.node_modules_path', base_path('node_modules'));

        $script = <<<'JS'
import puppeteer from 'puppeteer';
console.log(await puppeteer.executablePath());
JS;

        $result = Process::path(base_path())
            ->env(['NODE_PATH' => $modules])
            ->run([$node, '--input-type=module', '-e', $script]);

        if (! $result->successful()) {
            return null;
        }

        $path = trim($result->output());

        return $path !== '' && is_executable($path) ? $path : null;
    }

    private function commandExists(string $command): bool
    {
        $result = Process::run(['which', $command]);

        return $result->successful();
    }
}
