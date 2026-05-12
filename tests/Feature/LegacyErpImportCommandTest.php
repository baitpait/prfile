<?php

use App\Models\Client;
use App\Models\ClientPayment;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\InvoiceLine;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderLine;
use App\Models\Supplier;
use App\Models\SupplierPayment;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    $this->legacyPath = database_path('testing_legacy_erp_'.uniqid('', true).'.sqlite');
    if (file_exists($this->legacyPath)) {
        unlink($this->legacyPath);
    }
    touch($this->legacyPath);

    config([
        'database.connections.legacy_erp' => [
            'driver' => 'sqlite',
            'database' => $this->legacyPath,
            'prefix' => '',
            'foreign_key_constraints' => false,
        ],
    ]);
    DB::purge('legacy_erp');

    $l = DB::connection('legacy_erp');

    Schema::connection('legacy_erp')->dropAllTables();

    $l->statement('CREATE TABLE users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        firstname TEXT NOT NULL DEFAULT "",
        lastname TEXT NOT NULL DEFAULT "",
        username TEXT NOT NULL DEFAULT "",
        email TEXT NOT NULL,
        password TEXT NOT NULL,
        role_id INTEGER NOT NULL DEFAULT 1,
        statut INTEGER NOT NULL DEFAULT 1,
        deleted_at TEXT NULL
    )');

    $l->statement('CREATE TABLE clients (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        code INTEGER NOT NULL DEFAULT 0,
        email TEXT,
        country TEXT,
        city TEXT,
        phone TEXT,
        adresse TEXT,
        tax_number TEXT,
        opening_balance REAL NOT NULL DEFAULT 0,
        wallet_balance REAL NOT NULL DEFAULT 0,
        deleted_at TEXT NULL
    )');

    $l->statement('CREATE TABLE providers (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        code INTEGER NOT NULL DEFAULT 0,
        email TEXT,
        phone TEXT,
        country TEXT,
        city TEXT,
        adresse TEXT,
        tax_number TEXT,
        opening_balance REAL NOT NULL DEFAULT 0,
        wallet_balance REAL NOT NULL DEFAULT 0,
        deleted_at TEXT NULL
    )');

    $l->statement('CREATE TABLE products (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        code TEXT NOT NULL DEFAULT "",
        cost REAL NOT NULL DEFAULT 0,
        price REAL NOT NULL DEFAULT 0,
        min_price REAL NOT NULL DEFAULT 0,
        note TEXT,
        deleted_at TEXT NULL
    )');

    $l->statement('CREATE TABLE sales (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL DEFAULT 1,
        date TEXT NOT NULL,
        Ref TEXT NOT NULL DEFAULT "",
        client_id INTEGER NOT NULL,
        warehouse_id INTEGER NOT NULL DEFAULT 1,
        GrandTotal REAL NOT NULL DEFAULT 0,
        paid_amount REAL NOT NULL DEFAULT 0,
        payment_statut TEXT NOT NULL DEFAULT "paid",
        statut TEXT NOT NULL DEFAULT "completed",
        discount REAL NOT NULL DEFAULT 0,
        notes TEXT,
        deleted_at TEXT NULL
    )');

    $l->statement('CREATE TABLE sale_details (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        date TEXT NOT NULL,
        sale_id INTEGER NOT NULL,
        product_id INTEGER NOT NULL,
        price REAL NOT NULL,
        total REAL NOT NULL,
        quantity REAL NOT NULL
    )');

    $l->statement('CREATE TABLE payment_sales (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL DEFAULT 1,
        date TEXT NOT NULL,
        Ref TEXT NOT NULL,
        sale_id INTEGER NOT NULL,
        montant REAL NOT NULL,
        payment_method_id INTEGER,
        notes TEXT,
        deleted_at TEXT NULL
    )');

    $l->statement('CREATE TABLE purchases (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL DEFAULT 1,
        Ref TEXT NOT NULL,
        date TEXT NOT NULL,
        provider_id INTEGER NOT NULL,
        warehouse_id INTEGER NOT NULL DEFAULT 1,
        GrandTotal REAL NOT NULL DEFAULT 0,
        paid_amount REAL NOT NULL DEFAULT 0,
        statut TEXT NOT NULL DEFAULT "received",
        payment_statut TEXT NOT NULL DEFAULT "paid",
        discount REAL NOT NULL DEFAULT 0,
        notes TEXT,
        deleted_at TEXT NULL
    )');

    $l->statement('CREATE TABLE purchase_details (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        cost REAL NOT NULL,
        purchase_id INTEGER NOT NULL,
        product_id INTEGER NOT NULL,
        total REAL NOT NULL,
        quantity REAL NOT NULL
    )');

    $l->statement('CREATE TABLE payment_purchases (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL DEFAULT 1,
        date TEXT NOT NULL,
        Ref TEXT NOT NULL,
        purchase_id INTEGER NOT NULL,
        montant REAL NOT NULL,
        deleted_at TEXT NULL
    )');

    $l->statement('CREATE TABLE expenses (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        date TEXT NOT NULL,
        Ref TEXT NOT NULL,
        user_id INTEGER NOT NULL DEFAULT 1,
        expense_category_id INTEGER NOT NULL DEFAULT 1,
        warehouse_id INTEGER NOT NULL DEFAULT 1,
        account_id INTEGER,
        details TEXT NOT NULL,
        amount REAL NOT NULL,
        deleted_at TEXT NULL
    )');

    $l->table('users')->insert([
        'firstname' => 'Test',
        'lastname' => 'User',
        'username' => 'tester',
        'email' => 'erp_legacy_user@test.local',
        'password' => 'x',
        'role_id' => 1,
        'statut' => 1,
        'deleted_at' => null,
    ]);

    $l->table('clients')->insert([
        'name' => 'عميل تجريبي',
        'code' => 1001,
        'email' => 'client@test.local',
        'country' => 'PS',
        'city' => 'دورا',
        'phone' => '0599000000',
        'adresse' => 'عنوان',
        'tax_number' => null,
        'opening_balance' => 0,
        'wallet_balance' => 0,
        'deleted_at' => null,
    ]);

    $l->table('providers')->insert([
        'name' => 'مورد تجريبي',
        'code' => 2001,
        'email' => null,
        'phone' => '0599111111',
        'country' => 'PS',
        'city' => null,
        'adresse' => null,
        'tax_number' => null,
        'opening_balance' => 0,
        'wallet_balance' => 0,
        'deleted_at' => null,
    ]);

    $l->table('products')->insert([
        'name' => 'خدمة تصميم',
        'code' => 'P1',
        'cost' => 100,
        'price' => 500,
        'min_price' => 400,
        'note' => null,
        'deleted_at' => null,
    ]);

    $l->table('sales')->insert([
        'user_id' => 1,
        'date' => '2024-01-15',
        'Ref' => 'S-1',
        'client_id' => 1,
        'warehouse_id' => 1,
        'GrandTotal' => 500,
        'paid_amount' => 500,
        'payment_statut' => 'paid',
        'statut' => 'completed',
        'discount' => 0,
        'notes' => null,
        'deleted_at' => null,
    ]);

    $l->table('sale_details')->insert([
        'date' => '2024-01-15',
        'sale_id' => 1,
        'product_id' => 1,
        'price' => 500,
        'total' => 500,
        'quantity' => 1,
    ]);

    $l->table('payment_sales')->insert([
        'user_id' => 1,
        'date' => '2024-01-16',
        'Ref' => 'PS-1',
        'sale_id' => 1,
        'montant' => 500,
        'payment_method_id' => 1,
        'notes' => null,
        'deleted_at' => null,
    ]);

    $l->table('purchases')->insert([
        'user_id' => 1,
        'Ref' => 'P-1',
        'date' => '2024-02-01',
        'provider_id' => 1,
        'warehouse_id' => 1,
        'GrandTotal' => 300,
        'paid_amount' => 300,
        'statut' => 'received',
        'payment_statut' => 'paid',
        'discount' => 0,
        'notes' => null,
        'deleted_at' => null,
    ]);

    $l->table('purchase_details')->insert([
        'cost' => 300,
        'purchase_id' => 1,
        'product_id' => 1,
        'total' => 300,
        'quantity' => 1,
    ]);

    $l->table('payment_purchases')->insert([
        'user_id' => 1,
        'date' => '2024-02-02',
        'Ref' => 'PP-1',
        'purchase_id' => 1,
        'montant' => 300,
        'deleted_at' => null,
    ]);

    $l->table('expenses')->insert([
        'date' => '2024-03-01',
        'Ref' => 'E-1',
        'user_id' => 1,
        'expense_category_id' => 1,
        'warehouse_id' => 1,
        'account_id' => null,
        'details' => 'مصروف مكتب',
        'amount' => 50,
        'deleted_at' => null,
    ]);
});

afterEach(function () {
    DB::purge('legacy_erp');
    if (isset($this->legacyPath) && file_exists($this->legacyPath)) {
        @unlink($this->legacyPath);
    }
});

test('legacy erp dry-run reports row counts', function () {
    $exit = Artisan::call('legacy-erp:import', ['--dry-run' => true]);
    expect($exit)->toBe(0);
    expect(Artisan::output())->toContain('وضع التجربة');
});

test('legacy erp import creates clients invoices payments suppliers and expenses', function () {
    $exit = Artisan::call('legacy-erp:import');
    expect($exit)->toBe(0);

    expect(Client::query()->where('legacy_match_key', 'erp_client:1')->exists())->toBeTrue();
    expect(Invoice::query()->where('legacy_invoice_no', 'ERP-SALE-1')->exists())->toBeTrue();
    expect(InvoiceLine::query()->count())->toBe(1);
    expect(ClientPayment::query()->where('bank_reference', 'LEGACY_ERP_PS:1')->exists())->toBeTrue();
    expect(Supplier::query()->where('legacy_number', 'ERP-PROV-1')->exists())->toBeTrue();
    expect(PurchaseOrder::query()->where('legacy_po_no', 'ERP-PUR-1')->exists())->toBeTrue();
    expect(PurchaseOrderLine::query()->count())->toBe(1);
    expect(SupplierPayment::query()->where('bank_reference', 'LEGACY_ERP_PP:1')->exists())->toBeTrue();
    expect(Expense::query()->where('notes', 'like', 'LEGACY_ERP_EXP:1%')->exists())->toBeTrue();
    expect(Product::query()->where('product_code', 'ERP-PROD-1')->exists())->toBeTrue();
    expect(DB::table('import_audit')->where('source', 'legacy_erp')->exists())->toBeTrue();
});

test('legacy erp import is idempotent on second run', function () {
    Artisan::call('legacy-erp:import');
    $n1 = Invoice::query()->count();
    Artisan::call('legacy-erp:import');
    $n2 = Invoice::query()->count();
    expect($n2)->toBe($n1);
});
