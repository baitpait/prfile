<?php

namespace App\Filesystem;

use Illuminate\Filesystem\Filesystem as BaseFilesystem;

/**
 * PHP promotes tempnam() fallback notices to exceptions in Laravel.
 * Suppress the notice when the target directory is writable; fail clearly otherwise.
 */
class Filesystem extends BaseFilesystem
{
    public function replace($path, $content, $mode = null)
    {
        clearstatcache(true, $path);

        $path = realpath($path) ?: $path;
        $directory = dirname($path);

        if (! is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        if (! is_writable($directory)) {
            throw new \RuntimeException("Directory is not writable: {$directory}");
        }

        $prefix = substr(preg_replace('/[^a-zA-Z0-9]/', '', basename($path)), 0, 6) ?: 'laravel';
        $tempPath = @tempnam($directory, $prefix);

        if ($tempPath === false) {
            $this->put($path, $content, true);

            return;
        }

        if (! is_null($mode)) {
            @chmod($tempPath, $mode);
        } else {
            @chmod($tempPath, 0777 - umask());
        }

        file_put_contents($tempPath, $content);

        rename($tempPath, $path);
    }
}
