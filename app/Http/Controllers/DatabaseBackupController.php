<?php

namespace App\Http\Controllers;

use App\Services\DatabaseBackupService;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DatabaseBackupController extends Controller
{
    public function download(string $filename, DatabaseBackupService $backups): BinaryFileResponse
    {
        abort_unless(auth()->user()?->isManager(), 403);

        try {
            $path = $backups->resolveBackupPath($filename);
        } catch (RuntimeException) {
            abort(404);
        }

        return response()->download($path, $filename, [
            'Content-Type' => str_ends_with($filename, '.sqlite')
                ? 'application/x-sqlite3'
                : 'application/sql',
        ]);
    }
}
