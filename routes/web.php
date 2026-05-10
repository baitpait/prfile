<?php

use App\Http\Controllers\ClientStatementController;
use App\Http\Controllers\InvoicePrintController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('dashboard'));

Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', fn () => view('dashboard'))->name('dashboard');

    Route::get('/clients', fn () => view('clients.index'))->name('clients.index');
    Route::get('/clients/{client}/statement', [ClientStatementController::class, 'show'])->name('clients.statement');
    Route::get('/clients/{client}/statement/pdf', [ClientStatementController::class, 'pdf'])->name('clients.statement.pdf');

    Route::get('/suppliers', fn () => view('suppliers.index'))->name('suppliers.index');

    Route::get('/invoices', fn () => view('invoices.index'))->name('invoices.index');
    Route::get('/invoices/{invoice}/print', [InvoicePrintController::class, 'show'])->name('invoices.print');

    Route::get('/expenses', fn () => view('expenses.index'))->name('expenses.index');

    Route::get('/income-entries', fn () => view('income-entries.index'))->name('income-entries.index');

    Route::get('/payments', fn () => view('payments.index'))->name('payments.index');
});

require __DIR__.'/auth.php';
