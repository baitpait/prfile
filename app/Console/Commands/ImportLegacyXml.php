<?php

namespace App\Console\Commands;

use App\Services\LegacyXmlImporter;
use Illuminate\Console\Command;
use Throwable;

class ImportLegacyXml extends Command
{
    protected $signature = 'app:import-legacy-xml
        {directory : المسار إلى مجلد يحوي ملفات XML (مطلق أو نسبي من جذر المشروع)}
        {--fresh : حذف بيانات الوحدات التجارية والمستخدمين الاصطناعيين (staff@...) قبل الاستيراد}';

    protected $description = 'استيراد نسخة احتياطية XML (العملاء، الفواتير، الموردين، …) إلى قاعدة بيانات التطبيق';

    public function handle(): int
    {
        $raw = (string) $this->argument('directory');
        $path = str_starts_with($raw, DIRECTORY_SEPARATOR) || preg_match('#^[A-Za-z]:\\\\#', $raw) === 1
            ? $raw
            : base_path(trim($raw, '/'));

        $fresh = (bool) $this->option('fresh');

        if ($fresh && $this->input->isInteractive() && ! $this->confirm('سيتم حذف البيانات في الجداول التجارية والمستخدمين staff*@profilemedia.local. المتابعة؟', false)) {
            $this->warn('أُلغي الأمر.');

            return self::SUCCESS;
        }

        try {
            $importer = new LegacyXmlImporter(fn (string $m) => $this->line($m));
            $counts = $importer->import($path, $fresh);
        } catch (Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->newLine();
        $this->info('اكتمل الاستيراد.');
        $this->table(
            ['الجدول', 'العدد'],
            collect($counts)->map(fn (int $n, string $k) => [$k, number_format($n)])->values()->all()
        );

        return self::SUCCESS;
    }
}
