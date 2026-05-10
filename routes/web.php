<?php

use App\Http\Controllers\ClientStatementController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('dashboard'));

Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', fn () => view('dashboard'))->name('dashboard');

    Route::get('/clients', fn () => view('clients.index'))->name('clients.index');
    Route::get('/clients/{client}/statement', [ClientStatementController::class, 'show'])->name('clients.statement');
    Route::get('/clients/{client}/statement/pdf', [ClientStatementController::class, 'pdf'])->name('clients.statement.pdf');

    Route::get('/suppliers', fn () => view('suppliers.index'))->name('suppliers.index');
    Route::get('/invoices', fn () => view('invoices.index'))->name('invoices.index');
    Route::get('/expenses', fn () => view('expenses.index'))->name('expenses.index');
});

require __DIR__.'/auth.php';
