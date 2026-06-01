<?php

namespace App\Services;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use RuntimeException;

/**
 * Business Purpose: Create downloadable database backups for managers (SQLite file copy or MySQL dump).
 */
class DatabaseBackupService
{
    private const FILENAME_PATTERN = '/^laravel_(sqlite|mysql_full|mysql_data)_[0-9]{4}-[0-9]{2}-[0-9]{2}_[0-9]{6}\.(sqlite|sql)$/';

    public function backupDirectory(): string
    {
        $dir = database_path('backups');
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        return $dir;
    }

    public function connectionName(): string
    {
        return (string) config('database.default');
    }

    public function driverName(): string
    {
        return (string) config('database.connections.'.$this->connectionName().'.driver');
    }

    public function driverLabel(): string
    {
        return match ($this->driverName()) {
            'sqlite' => 'SQLite',
            'mysql' => 'MySQL',
            'mariadb' => 'MariaDB',
            default => $this->driverName(),
        };
    }

    /**
     * @return list<array{filename: string, size: int, size_human: string, created_at: string, type: string}>
     */
    public function listBackups(int $limit = 15): array
    {
        $dir = $this->backupDirectory();
        $files = collect(File::files($dir))
            ->filter(fn ($file) => preg_match(self::FILENAME_PATTERN, $file->getFilename()) === 1)
            ->sortByDesc(fn ($file) => $file->getMTime())
            ->take($limit)
            ->map(function ($file) {
                $name = $file->getFilename();
                $type = str_contains($name, 'sqlite') ? 'sqlite'
                    : (str_contains($name, 'mysql_data') ? 'mysql_data' : 'mysql_full');

                return [
                    'filename' => $name,
                    'size' => $file->getSize(),
                    'size_human' => $this->formatBytes($file->getSize()),
                    'created_at' => date('Y-m-d H:i', $file->getMTime()),
                    'type' => $type,
                ];
            })
            ->values()
            ->all();

        return $files;
    }

    public function createSqliteBackup(): string
    {
        if ($this->driverName() !== 'sqlite') {
            throw new RuntimeException('النسخة الكاملة بملف SQLite متاحة فقط عند استخدام SQLite محلياً.');
        }

        $source = (string) config('database.connections.'.$this->connectionName().'.database');
        if ($source === ':memory:') {
            throw new RuntimeException('لا يمكن نسخ قاعدة :memory: — استخدم ملف SQLite فعلي أو صدّر INSERT فقط.');
        }
        if (! str_starts_with($source, DIRECTORY_SEPARATOR)) {
            $source = database_path($source);
        }
        if (! is_file($source)) {
            $fallback = database_path('database.sqlite');
            if (is_file($fallback)) {
                $source = $fallback;
            }
        }
        if (! is_file($source)) {
            throw new RuntimeException('ملف قاعدة البيانات غير موجود.');
        }

        $filename = 'laravel_sqlite_'.now()->format('Y-m-d_His').'.sqlite';
        $target = $this->backupDirectory().DIRECTORY_SEPARATOR.$filename;

        if (! copy($source, $target)) {
            throw new RuntimeException('تعذّر نسخ ملف SQLite.');
        }

        return $filename;
    }

    public function createMysqlFullBackup(): string
    {
        if (! in_array($this->driverName(), ['mysql', 'mariadb'], true)) {
            throw new RuntimeException('النسخة الكاملة MySQL متاحة فقط عند اتصال MySQL/MariaDB.');
        }

        $filename = 'laravel_mysql_full_'.now()->format('Y-m-d_His').'.sql';
        $target = $this->backupDirectory().DIRECTORY_SEPARATOR.$filename;

        $this->runMysqldump($target);

        return $filename;
    }

    public function createMysqlDataOnlyBackup(): string
    {
        $filename = 'laravel_mysql_data_'.now()->format('Y-m-d_His').'.sql';
        $target = $this->backupDirectory().DIRECTORY_SEPARATOR.$filename;

        $exit = Artisan::call('export:mysql-data', ['--output' => $target]);
        if ($exit !== 0 || ! is_file($target)) {
            throw new RuntimeException('تعذّر تصدير بيانات INSERT: '.trim(Artisan::output()));
        }

        return $filename;
    }

    public function resolveBackupPath(string $filename): string
    {
        if (preg_match(self::FILENAME_PATTERN, $filename) !== 1) {
            throw new RuntimeException('اسم ملف غير صالح.');
        }

        $path = $this->backupDirectory().DIRECTORY_SEPARATOR.$filename;
        if (! is_file($path)) {
            throw new RuntimeException('الملف غير موجود.');
        }

        return $path;
    }

    private function runMysqldump(string $targetPath): void
    {
        $connection = config('database.connections.'.$this->connectionName());
        if (! is_array($connection)) {
            throw new RuntimeException('إعدادات الاتصال غير صالحة.');
        }

        $host = (string) ($connection['host'] ?? '127.0.0.1');
        $port = (string) ($connection['port'] ?? '3306');
        $database = (string) ($connection['database'] ?? '');
        $username = (string) ($connection['username'] ?? '');
        $password = (string) ($connection['password'] ?? '');

        if ($database === '') {
            throw new RuntimeException('اسم قاعدة البيانات غير مضبوط.');
        }

        $mysqldump = (string) (config('database.backup.mysqldump_path') ?: 'mysqldump');

        $process = Process::timeout(300)->env([
            'MYSQL_PWD' => $password,
        ])->run([
            $mysqldump,
            '--host='.$host,
            '--port='.$port,
            '--user='.$username,
            '--single-transaction',
            '--routines',
            '--triggers',
            $database,
        ]);

        if (! $process->successful()) {
            throw new RuntimeException(
                'فشل mysqldump. تأكد من تثبيت الأداة على السيرفر أو اضبط MYSQLDUMP_PATH في .env. '
                .trim($process->errorOutput())
            );
        }

        File::put($targetPath, $process->output());
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes < 1024) {
            return $bytes.' B';
        }
        if ($bytes < 1024 * 1024) {
            return round($bytes / 1024, 1).' KB';
        }

        return round($bytes / (1024 * 1024), 2).' MB';
    }
}
