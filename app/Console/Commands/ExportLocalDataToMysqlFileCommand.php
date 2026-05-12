<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * يصدّر بيانات التطبيق من اتصال Laravel الحالي إلى ملف INSERT لـ MySQL
 * (للاستيراد اليدوي على السيرفر بعد تشغيل المايغريشن على قاعدة فارغة).
 */
class ExportLocalDataToMysqlFileCommand extends Command
{
    protected $signature = 'export:mysql-data
                            {--output= : مسار ملف .sql (افتراضي: سطح المكتب)}';

    protected $description = 'تصدير بيانات قاعدة البيانات الحالية إلى ملف SQL (MySQL) على سطح المكتب';

    /** @return list<string> */
    private function tablesInDependencyOrder(): array
    {
        return [
            'users',
            'password_reset_tokens',
            'sessions',
            'clients',
            'suppliers',
            'client_contacts',
            'supplier_contacts',
            'products',
            'product_currency_prices',
            'invoices',
            'invoice_lines',
            'client_payments',
            'purchase_orders',
            'purchase_order_lines',
            'supplier_payments',
            'expenses',
            'income_entries',
            'legacy_catalog_products',
            'legacy_catalog_projects',
            'import_audit',
        ];
    }

    public function handle(): int
    {
        $connection = DB::connection();
        $pdo = $connection->getPdo();
        $driver = $connection->getDriverName();

        $defaultPath = $this->defaultOutputPath();
        $path = $this->option('output') ?: $defaultPath;

        $dir = dirname($path);
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $this->info('الاتصال: '.$driver);
        $this->info('الملف: '.$path);

        $lines = [];
        $lines[] = '-- بروفايل ميدا — تصدير بيانات للاستيراد اليدوي على MySQL';
        $lines[] = '-- أنشئ بعد: php artisan migrate --force على السيرفر (قاعدة فارغة من الجداول فقط)';
        $lines[] = '';
        $lines[] = 'SET NAMES utf8mb4;';
        $lines[] = 'SET FOREIGN_KEY_CHECKS = 0;';
        $lines[] = '';

        foreach ($this->tablesInDependencyOrder() as $table) {
            if (! Schema::hasTable($table)) {
                $this->warn('تخطّي (غير موجود): '.$table);

                continue;
            }

            $rows = $connection->table($table)->get();
            if ($rows->isEmpty()) {
                $this->line('فارغ: '.$table);

                continue;
            }

            $columns = array_keys((array) $rows->first());
            $colList = '`'.implode('`,`', $columns).'`';

            foreach ($rows as $row) {
                $values = [];
                foreach ($columns as $col) {
                    $values[] = $this->quoteMysqlValue($pdo, $row->{$col} ?? null);
                }
                $lines[] = 'INSERT INTO `'.$table.'` ('.$colList.') VALUES ('.implode(',', $values).');';
            }
            $lines[] = '';
            $this->info('صُدّر: '.$table.' ('.$rows->count().' صف)');
        }

        $lines[] = 'SET FOREIGN_KEY_CHECKS = 1;';
        $lines[] = '';

        file_put_contents($path, implode("\n", $lines));

        $readmePath = preg_replace('/\.sql$/', '_تعليمات_الاستيراد.txt', $path) ?? ($path.'_تعليمات.txt');
        file_put_contents($readmePath, $this->readmeArabic());

        $this->info('تم. ملف SQL + ملف التعليمات بجانبه.');

        return self::SUCCESS;
    }

    private function defaultOutputPath(): string
    {
        $home = getenv('HOME') ?: getenv('USERPROFILE') ?: '';
        $desktop = $home !== '' ? $home.DIRECTORY_SEPARATOR.'Desktop' : storage_path('app/exports');

        return $desktop.DIRECTORY_SEPARATOR.'profile_media_mysql_export_'.date('Y-m-d_His').'.sql';
    }

    private function readmeArabic(): string
    {
        return <<<'TXT'
بروفايل ميدا — استيراد يدوي على السيرفر (MySQL)
============================================

1) على السيرفر بعد رفع الملف:
   cd /home/baitpait/public_html/profile

2) تأكد أن المايغريشن نُفّذ وقاعدة البيانات تحتوي الجداول فقط (بدون بيانات)،
   أو احذف بيانات الجداول يدوياً إن أردت استبدالها بالكامل.

3) استيراد الملف (مثال — عدّل المستخدم واسم القاعدة):
   mysql -u baitpait_profile -p baitpait_profile < profile_media_mysql_export_....sql

   أو من phpMyAdmin: استيراد → اختر ملف .sql

4) إن ظهر تعارض في المفاتيح (duplicate entry)، القاعدة ليست فارغة:
   إما قاعدة جديدة، أو امسح بيانات الجداول بالترتيب المناسب ثم أعد الاستيراد.

5) لا ترفع ملف .sql إلى Git إن احتوى بيانات حساسة.
TXT;
    }

    private function quoteMysqlValue(\PDO $pdo, mixed $value): string
    {
        if ($value === null) {
            return 'NULL';
        }

        if ($value === true || $value === false) {
            return $value ? '1' : '0';
        }

        if (is_int($value)) {
            return (string) $value;
        }

        if (is_float($value)) {
            return $this->formatFloat($value);
        }

        if (is_string($value) && is_numeric($value) && preg_match('/^-?\d+(\.\d+)?$/', $value)) {
            return $value;
        }

        if ($value instanceof \DateTimeInterface) {
            return $pdo->quote($value->format('Y-m-d H:i:s'));
        }

        if (is_string($value)) {
            return $pdo->quote($value);
        }

        if (is_array($value) || is_object($value)) {
            return $pdo->quote(json_encode($value, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE));
        }

        return $pdo->quote((string) $value);
    }

    private function formatFloat(float $value): string
    {
        if (is_nan($value) || is_infinite($value)) {
            return 'NULL';
        }

        return rtrim(rtrim(sprintf('%.6F', $value), '0'), '.') ?: '0';
    }
}
