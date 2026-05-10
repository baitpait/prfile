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
    Route::get('/invoices/create', function () {
        abort_unless(auth()->user()->isAccountant(), 403);
        return view('invoices.create');
    })->name('invoices.create');
    Route::get('/invoices/{invoice}/edit', function (\App\Models\Invoice $invoice) {
        abort_unless(auth()->user()->isAccountant(), 403);
        return view('invoices.edit', compact('invoice'));
    })->name('invoices.edit');
    Route::get('/invoices/{invoice}/print', [InvoicePrintController::class, 'show'])->name('invoices.print');

    Route::get('/expenses', fn () => view('expenses.index'))->name('expenses.index');
    Route::get('/expenses/create', function () {
        abort_unless(auth()->user()->isAccountant(), 403);
        return view('expenses.create');
    })->name('expenses.create');
    Route::get('/expenses/{expense}/edit', function (\App\Models\Expense $expense) {
        abort_unless(auth()->user()->isAccountant(), 403);
        return view('expenses.edit', compact('expense'));
    })->name('expenses.edit');
    Route::get('/expenses/{expense}', fn (\App\Models\Expense $expense) => view('expenses.show', compact('expense')))->name('expenses.show');
    Route::delete('/expenses/{expense}', function (\App\Models\Expense $expense) {
        abort_unless(auth()->user()->isManager(), 403);
        $expense->delete();
        return redirect()->route('expenses.index')->with('toast', 'تم حذف المصروف');
    })->name('expenses.destroy');

    Route::get('/income-entries', fn () => view('income-entries.index'))->name('income-entries.index');
    Route::get('/income-entries/create', function () {
        abort_unless(auth()->user()->isAccountant(), 403);
        return view('income-entries.create');
    })->name('income-entries.create');
    Route::get('/income-entries/{incomeEntry}/edit', function (\App\Models\IncomeEntry $incomeEntry) {
        abort_unless(auth()->user()->isAccountant(), 403);
        return view('income-entries.edit', compact('incomeEntry'));
    })->name('income-entries.edit');
    Route::get('/income-entries/{incomeEntry}', fn (\App\Models\IncomeEntry $incomeEntry) => view('income-entries.show', compact('incomeEntry')))->name('income-entries.show');
    Route::delete('/income-entries/{incomeEntry}', function (\App\Models\IncomeEntry $incomeEntry) {
        abort_unless(auth()->user()->isManager(), 403);
        $incomeEntry->delete();
        return redirect()->route('income-entries.index')->with('toast', 'تم حذف الإيراد');
    })->name('income-entries.destroy');

    Route::get('/payments', fn () => view('payments.index'))->name('payments.index');
    Route::get('/payments/create', function () {
        abort_unless(auth()->user()->isAccountant(), 403);
        return view('payments.create');
    })->name('payments.create');
    Route::get('/payments/{payment}/edit', function (\App\Models\ClientPayment $payment) {
        abort_unless(auth()->user()->isAccountant(), 403);
        return view('payments.edit', compact('payment'));
    })->name('payments.edit');
    Route::get('/payments/{payment}', fn (\App\Models\ClientPayment $payment) => view('payments.show', compact('payment')))->name('payments.show');
    Route::delete('/payments/{payment}', function (\App\Models\ClientPayment $payment) {
        abort_unless(auth()->user()->isManager(), 403);
        $payment->delete();
        return redirect()->route('payments.index')->with('toast', 'تم حذف الدفعة');
    })->name('payments.destroy');

    Route::get('/clients/create', function () {
        abort_unless(auth()->user()->isAccountant(), 403);
        return view('clients.create');
    })->name('clients.create');
    Route::get('/clients/{client}/edit', function (\App\Models\Client $client) {
        abort_unless(auth()->user()->isAccountant(), 403);
        return view('clients.edit', compact('client'));
    })->name('clients.edit');
    Route::delete('/clients/{client}', function (\App\Models\Client $client) {
        abort_unless(auth()->user()->isManager(), 403);
        $client->delete();
        return redirect()->route('clients.index')->with('toast', 'تم حذف العميل');
    })->name('clients.destroy');
    Route::get('/clients/{client}', function (\App\Models\Client $client) {
        $client->load(['invoices' => fn($q) => $q->latest('document_date'), 'payments' => fn($q) => $q->latest('paid_at')]);
        return view('clients.show', compact('client'));
    })->name('clients.show');

    Route::get('/suppliers/create', function () {
        abort_unless(auth()->user()->isAccountant(), 403);
        return view('suppliers.create');
    })->name('suppliers.create');
    Route::get('/suppliers/{supplier}/edit', function (\App\Models\Supplier $supplier) {
        abort_unless(auth()->user()->isAccountant(), 403);
        return view('suppliers.edit', compact('supplier'));
    })->name('suppliers.edit');
    Route::get('/suppliers/{supplier}', fn (\App\Models\Supplier $supplier) => view('suppliers.show', compact('supplier')))->name('suppliers.show');
    Route::delete('/suppliers/{supplier}', function (\App\Models\Supplier $supplier) {
        abort_unless(auth()->user()->isManager(), 403);
        $supplier->delete();
        return redirect()->route('suppliers.index')->with('toast', 'تم حذف المورد');
    })->name('suppliers.destroy');

    Route::get('/users', function () {
        abort_unless(auth()->user()->isManager(), 403);
        return view('users.index');
    })->name('users.index');
    Route::get('/users/create', function () {
        abort_unless(auth()->user()->isManager(), 403);
        return view('users.create');
    })->name('users.create');
    Route::get('/users/{user}/edit', function (\App\Models\User $user) {
        abort_unless(auth()->user()->isManager(), 403);
        return view('users.edit', compact('user'));
    })->name('users.edit');
});

require __DIR__.'/auth.php';
