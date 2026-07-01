<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class StorageDoctorCommand extends Command
{
    protected $signature = 'storage:doctor';

    protected $description = 'Diagnose storage/framework permissions for Blade/Livewire (tempnam errors)';

    public function handle(): int
    {
        $paths = [
            'storage',
            'storage/framework',
            'storage/framework/views',
            'storage/framework/cache',
            'storage/logs',
            'bootstrap/cache',
        ];

        $this->info('Storage doctor — '.PHP_OS_FAMILY.' / PHP '.PHP_VERSION);
        $this->line('  process user: '.(function_exists('posix_getpwuid') ? (posix_getpwuid(posix_geteuid())['name'] ?? '?') : get_current_user()));
        $this->line('  project owner: '.(function_exists('posix_getpwuid') ? (posix_getpwuid(fileowner(base_path()))['name'] ?? '?') : '?'));
        $this->newLine();

        $failed = false;

        foreach ($paths as $relative) {
            $absolute = base_path($relative);
            $exists = is_dir($absolute);
            $writable = $exists && is_writable($absolute);
            $owner = $exists && function_exists('posix_getpwuid')
                ? (posix_getpwuid(fileowner($absolute))['name'] ?? (string) fileowner($absolute))
                : '?';

            $status = ! $exists ? 'MISSING' : ($writable ? 'writable' : 'NOT WRITABLE');
            $this->line(sprintf('  %-28s %s (owner: %s)', $relative, $status, $owner));

            if (! $writable) {
                $failed = true;
            }
        }

        $this->newLine();

        $views = storage_path('framework/views');
        if (! is_dir($views)) {
            File::makeDirectory($views, 0755, true);
            $this->warn("Created {$views}");
        }

        $testFile = $views.'/.write-test-'.uniqid();
        $writeOk = @file_put_contents($testFile, 'ok') !== false;
        if ($writeOk) {
            @unlink($testFile);
            $this->info('Write test in storage/framework/views: OK');
        } else {
            $failed = true;
            $this->error('Write test in storage/framework/views: FAILED');
        }

        $this->newLine();

        if ($failed) {
            $owner = function_exists('posix_getpwuid') ? (posix_getpwuid(fileowner(base_path()))['name'] ?? 'baitpait') : 'baitpait';
            $this->error("Fix: chown storage to the PHP/web user (often site owner «{$owner}», not webuzo):");
            $this->line("  chown -R {$owner}:{$owner} storage bootstrap/cache");
            $this->line('  chmod -R ug+rwx storage bootstrap/cache');
            $this->line('  php artisan optimize:clear');
            $this->line('  php artisan config:cache && php artisan route:cache');
            $this->line("  chown -R {$owner}:{$owner} storage bootstrap/cache");
            $this->line("  su -s /bin/bash {$owner} -c 'touch ".storage_path('framework/views/.write-test')."'");

            return self::FAILURE;
        }

        $this->info('Storage looks healthy for Blade compilation.');

        return self::SUCCESS;
    }
}
