<?php

use App\Livewire\DatabaseBackupPage;
use App\Models\User;
use App\Services\DatabaseBackupService;
use Livewire\Livewire;

test('database backup page is manager only', function () {
    $accountant = User::factory()->create(['role' => 'accountant', 'is_active' => true]);
    $manager = User::factory()->create(['role' => 'manager', 'is_active' => true]);

    $this->actingAs($accountant)
        ->get(route('database-backup.index'))
        ->assertForbidden();

    $this->actingAs($manager)
        ->get(route('database-backup.index'))
        ->assertOk()
        ->assertSee('نسخ احتياطي لقاعدة البيانات');
});

test('backup directory defaults to writable storage path', function () {
    $service = app(DatabaseBackupService::class);

    expect($service->backupDirectory())->toBe(storage_path('app/database-backups'));
    expect(is_dir($service->backupDirectory()))->toBeTrue();
    expect(is_writable($service->backupDirectory()))->toBeTrue();
});

test('sqlite backup creates downloadable file', function () {
    if (config('database.connections.sqlite.database') === ':memory:') {
        $this->markTestSkipped('In-memory SQLite in tests');
    }

    $manager = User::factory()->create(['role' => 'manager', 'is_active' => true]);
    $service = app(DatabaseBackupService::class);

    $filename = $service->createSqliteBackup();
    $path = $service->resolveBackupPath($filename);

    expect(is_file($path))->toBeTrue();
    expect(filesize($path))->toBeGreaterThan(0);

    $this->actingAs($manager)
        ->get(route('database-backup.download', ['filename' => $filename]))
        ->assertOk();

    @unlink($path);
});

test('mysql data only backup runs export command', function () {
    $manager = User::factory()->create(['role' => 'manager', 'is_active' => true]);
    $service = app(DatabaseBackupService::class);

    $filename = $service->createMysqlDataOnlyBackup();
    $path = $service->resolveBackupPath($filename);

    expect(is_file($path))->toBeTrue();
    expect(file_get_contents($path))->toContain('INSERT INTO');

    $this->actingAs($manager)
        ->get(route('database-backup.download', ['filename' => $filename]))
        ->assertOk();

    @unlink($path);
});

test('invalid backup filename is rejected', function () {
    $manager = User::factory()->create(['role' => 'manager', 'is_active' => true]);

    $this->actingAs($manager)
        ->get(route('database-backup.download', ['filename' => '../.env']))
        ->assertNotFound();
});

test('livewire sqlite backup redirects to download', function () {
    if (config('database.connections.sqlite.database') === ':memory:') {
        $this->markTestSkipped('In-memory SQLite in tests');
    }

    $manager = User::factory()->create(['role' => 'manager', 'is_active' => true]);

    Livewire::actingAs($manager)
        ->test(DatabaseBackupPage::class)
        ->call('createSqliteBackup')
        ->assertRedirect();
});
