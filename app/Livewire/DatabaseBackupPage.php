<?php

namespace App\Livewire;

use App\Services\DatabaseBackupService;
use Livewire\Component;
use RuntimeException;

class DatabaseBackupPage extends Component
{
    public string $statusMessage = '';

    public string $statusType = 'success';

    public function mount(): void
    {
        abort_unless(auth()->user()->isManager(), 403);
    }

    public function createSqliteBackup(DatabaseBackupService $backups): void
    {
        $this->runBackup(fn () => $backups->createSqliteBackup());
    }

    public function createMysqlFullBackup(DatabaseBackupService $backups): void
    {
        $this->runBackup(fn () => $backups->createMysqlFullBackup());
    }

    public function createMysqlDataBackup(DatabaseBackupService $backups): void
    {
        $this->runBackup(fn () => $backups->createMysqlDataOnlyBackup());
    }

    private function runBackup(callable $creator): void
    {
        $this->statusMessage = '';
        $this->statusType = 'success';

        try {
            $filename = $creator();
            $this->redirect(route('database-backup.download', ['filename' => $filename]), navigate: false);
        } catch (RuntimeException $e) {
            $this->statusMessage = $e->getMessage();
            $this->statusType = 'error';
            $this->dispatch('toast', message: $e->getMessage(), type: 'error');
        }
    }

    public function render(DatabaseBackupService $backups)
    {
        return view('livewire.database-backup-page', [
            'driverLabel' => $backups->driverLabel(),
            'driverName' => $backups->driverName(),
            'backups' => $backups->listBackups(),
            'backupDir' => $backups->backupDirectory(),
        ]);
    }
}
