<?php

namespace App\Console\Commands;

use App\Services\Documents\PrintViewPdfRenderer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Process;
use Spatie\Browsershot\Browsershot;

class BrowsershotCheckCommand extends Command
{
    protected $signature = 'browsershot:check';

    protected $description = 'Diagnose Node/Puppeteer/Chrome for document PDF generation';

    public function handle(PrintViewPdfRenderer $renderer): int
    {
        $this->info('Browsershot / Puppeteer diagnostics');
        $this->newLine();

        foreach ($renderer->diagnostics() as $key => $value) {
            $this->line(sprintf('  %-22s %s', $key.':', $value));
        }

        $this->newLine();

        if (! $renderer->isAvailable()) {
            $this->error('Chrome path could not be resolved. Run: PUPPETEER_CACHE_DIR='.config('browsershot.puppeteer_cache_dir').' npm ci');

            return self::FAILURE;
        }

        $this->info('Generating test PDF...');

        try {
            $node = config('browsershot.node_binary', 'node');
            $modules = config('browsershot.node_modules_path', base_path('node_modules'));
            $cacheDir = (string) config('browsershot.puppeteer_cache_dir');

            $shot = Browsershot::html('<html><body><p>PDF OK</p></body></html>')
                ->showBackground()
                ->emulateMedia('print')
                ->format('A4')
                ->setDelay(200)
                ->setNodeBinary($node)
                ->setNodeModulePath($modules)
                ->setEnvironmentOptions(array_filter([
                    'PUPPETEER_CACHE_DIR' => $cacheDir !== '' ? $cacheDir : null,
                    'HOME' => storage_path('app'),
                ]))
                ->addChromiumArguments(['disable-dev-shm-usage', 'disable-gpu']);

            $chrome = trim(Process::path(base_path())
                ->env(array_filter([
                    'NODE_PATH' => $modules,
                    'PUPPETEER_CACHE_DIR' => $cacheDir !== '' ? $cacheDir : null,
                    'HOME' => storage_path('app'),
                ]))
                ->run([$node, '--input-type=module', '-e', "import puppeteer from 'puppeteer'; console.log(await puppeteer.executablePath());"])
                ->output());

            if ($chrome !== '' && is_executable($chrome)) {
                $shot->setChromePath($chrome);
            }

            if (config('browsershot.no_sandbox', false)) {
                $shot->noSandbox();
            }

            $bytes = strlen($shot->pdf());
            $this->info("Test PDF generated successfully ({$bytes} bytes).");

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('Test PDF failed: '.$e->getMessage());

            return self::FAILURE;
        }
    }
}
