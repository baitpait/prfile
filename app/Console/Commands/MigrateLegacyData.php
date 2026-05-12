<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class MigrateLegacyData extends Command
{
    protected $signature = 'app:migrate-legacy {--fresh : احذف البيانات المرحَّلة قبل البدء}';

    protected $description = 'ترحيل بيانات business_v1.sqlite إلى قاعدة التطبيق الجديدة';

    private array $staffMap = [];

    public function handle(): int
    {
        $legacy = DB::connection('legacy');

        if ($this->option('fresh')) {
            $this->warn('تنظيف البيانات المرحَّلة سابقاً...');
            $this->truncateAll();
        }

        $this->info('بدء الترحيل من business_v1.sqlite...');

        DB::transaction(function () use ($legacy) {
            $this->migrateStaffs($legacy);
            $this->migrateClients($legacy);
            $this->migrateClientContacts($legacy);
            $this->migrateSuppliers($legacy);
            $this->migrateSupplierContacts($legacy);
            $this->migrateInvoices($legacy);
            $this->migrateInvoiceLines($legacy);
            $this->migratePurchaseOrders($legacy);
            $this->migratePurchaseOrderLines($legacy);
            $this->migrateClientPayments($legacy);
            $this->migrateSupplierPayments($legacy);
            $this->migrateExpenses($legacy);
            $this->migrateIncomeEntriesAsClientPayments($legacy);
            $this->migrateLegacyCatalog($legacy);
            $this->migrateImportAudit($legacy);
        });

        $this->newLine();
        $this->info('اكتمل الترحيل بنجاح ✓');
        $this->printSummary();

        return self::SUCCESS;
    }

    private function migrateStaffs($legacy): void
    {
        $staffs = $legacy->table('staffs')->get();
        $this->info("موظفون: {$staffs->count()}");

        foreach ($staffs as $s) {
            $email = $s->email ?: "staff{$s->id}@profilemedia.local";

            $existing = DB::table('users')->where('email', $email)->value('id');
            if ($existing) {
                $this->staffMap[$s->id] = $existing;

                continue;
            }

            $userId = DB::table('users')->insertGetId([
                'full_name' => $s->full_name,
                'email' => $email,
                'password' => Hash::make('change-me-'.$s->id),
                'role' => 'viewer',
                'is_active' => (bool) $s->is_active,
                'created_at' => $s->created_at,
                'updated_at' => $s->updated_at,
            ]);

            $this->staffMap[$s->id] = $userId;
        }

        $this->line("  → تمّ ربط {$staffs->count()} موظف بمستخدمين");
    }

    private function migrateClients($legacy): void
    {
        $rows = $legacy->table('clients')->get();
        $this->info("عملاء: {$rows->count()}");
        $inserted = 0;

        foreach ($rows as $r) {
            if (DB::table('clients')->where('id', $r->id)->exists()) {
                continue;
            }

            DB::table('clients')->insert([
                'id' => $r->id,
                'legacy_number' => $r->legacy_number,
                'legacy_match_key' => $r->legacy_match_key,
                'business_name' => $r->business_name,
                'first_name' => $r->first_name,
                'last_name' => $r->last_name,
                'email' => $r->email,
                'phone_primary' => $r->phone_primary,
                'phone_secondary' => $r->phone_secondary,
                'address_line1' => $r->address_line1,
                'address_line2' => $r->address_line2,
                'city' => $r->city,
                'state_region' => $r->state_region,
                'postal_code' => $r->postal_code,
                'country_code' => $r->country_code,
                'notes' => $this->cleanNotes($r->notes),
                'assigned_user_id' => $this->staffMap[$r->assigned_staff_id] ?? null,
                'source_row_json' => $r->source_row_json,
                'deleted_at' => $r->is_deleted ? $r->updated_at : null,
                'created_at' => $r->created_at,
                'updated_at' => $r->updated_at,
            ]);
            $inserted++;
        }
        $this->line("  → {$inserted} عميل");
    }

    private function migrateClientContacts($legacy): void
    {
        $rows = $legacy->table('client_contacts')->get();
        $this->info("جهات اتصال عملاء: {$rows->count()}");
        $inserted = 0;

        foreach ($rows as $r) {
            if (DB::table('client_contacts')->where('id', $r->id)->exists()) {
                continue;
            }

            DB::table('client_contacts')->insert([
                'id' => $r->id,
                'client_id' => $r->client_id,
                'label' => $r->label ?? null,
                'first_name' => $r->first_name,
                'last_name' => $r->last_name,
                'email' => $r->email,
                'phone_home' => $r->phone_home,
                'phone_mobile' => $r->phone_mobile,
                'notes' => $r->notes,
                'source_row_json' => $r->source_row_json,
                'deleted_at' => $r->is_deleted ? $r->updated_at : null,
                'created_at' => $r->created_at,
                'updated_at' => $r->updated_at,
            ]);
            $inserted++;
        }
        $this->line("  → {$inserted} جهة اتصال");
    }

    private function migrateSuppliers($legacy): void
    {
        $rows = $legacy->table('suppliers')->get();
        $this->info("موردون: {$rows->count()}");
        $inserted = 0;

        foreach ($rows as $r) {
            if (DB::table('suppliers')->where('id', $r->id)->exists()) {
                continue;
            }

            DB::table('suppliers')->insert([
                'id' => $r->id,
                'legacy_number' => $r->legacy_number,
                'business_name' => $r->business_name,
                'first_name' => $r->first_name,
                'last_name' => $r->last_name,
                'email' => $r->email,
                'phone_primary' => $r->phone_primary,
                'phone_secondary' => $r->phone_secondary,
                'address_line1' => $r->address_line1,
                'address_line2' => $r->address_line2,
                'city' => $r->city,
                'state_region' => $r->state_region,
                'postal_code' => $r->postal_code,
                'country_code' => $r->country_code,
                'notes' => $r->notes,
                'assigned_user_id' => $this->staffMap[$r->assigned_staff_id] ?? null,
                'source_row_json' => $r->source_row_json,
                'deleted_at' => $r->is_deleted ? $r->updated_at : null,
                'created_at' => $r->created_at,
                'updated_at' => $r->updated_at,
            ]);
            $inserted++;
        }
        $this->line("  → {$inserted} مورد");
    }

    private function migrateSupplierContacts($legacy): void
    {
        $rows = $legacy->table('supplier_contacts')->get();
        $this->info("جهات اتصال موردين: {$rows->count()}");
        $inserted = 0;

        foreach ($rows as $r) {
            if (DB::table('supplier_contacts')->where('id', $r->id)->exists()) {
                continue;
            }

            DB::table('supplier_contacts')->insert([
                'id' => $r->id,
                'supplier_id' => $r->supplier_id,
                'label' => null,
                'first_name' => $r->first_name,
                'last_name' => $r->last_name,
                'email' => $r->email,
                'phone_home' => $r->phone_home,
                'phone_mobile' => $r->phone_mobile,
                'notes' => $r->notes,
                'source_row_json' => $r->source_row_json,
                'deleted_at' => $r->is_deleted ? $r->updated_at : null,
                'created_at' => $r->created_at,
                'updated_at' => $r->updated_at,
            ]);
            $inserted++;
        }
        $this->line("  → {$inserted} جهة اتصال");
    }

    private function migrateInvoices($legacy): void
    {
        $rows = $legacy->table('invoices')->get();
        $this->info("فواتير: {$rows->count()}");
        $inserted = 0;

        foreach ($rows as $r) {
            if (DB::table('invoices')->where('id', $r->id)->exists()) {
                continue;
            }

            DB::table('invoices')->insert([
                'id' => $r->id,
                'client_id' => $r->client_id,
                'legacy_invoice_no' => $r->legacy_invoice_no,
                'document_date' => $r->document_date,
                'issue_date' => $r->issue_date,
                'due_date' => $r->due_date,
                'currency_code' => $r->currency_code,
                'discount_amount' => $r->discount_amount,
                'total_amount' => $r->total_amount,
                'notes' => $r->notes,
                'status' => $r->status,
                'recorded_by_user_id' => $this->staffMap[$r->recorded_by_staff_id] ?? null,
                'source_row_json' => $r->source_row_json,
                'deleted_at' => $r->is_deleted ? $r->updated_at : null,
                'created_at' => $r->created_at,
                'updated_at' => $r->updated_at,
            ]);
            $inserted++;
        }
        $this->line("  → {$inserted} فاتورة");
    }

    private function migrateInvoiceLines($legacy): void
    {
        $rows = $legacy->table('invoice_lines')->get();
        $this->info("بنود فواتير: {$rows->count()}");
        $inserted = 0;
        $chunk = [];

        foreach ($rows as $r) {
            if (DB::table('invoice_lines')->where('id', $r->id)->exists()) {
                continue;
            }

            $chunk[] = [
                'id' => $r->id,
                'invoice_id' => $r->invoice_id,
                'product_id' => null,
                'line_order' => $r->line_order,
                'title' => $r->title,
                'description' => $r->description,
                'unit_price' => $r->unit_price,
                'quantity' => $r->quantity,
                'line_total' => $r->line_total,
                'source_row_json' => $r->source_row_json,
                'created_at' => $r->created_at,
                'updated_at' => $r->updated_at,
            ];
            $inserted++;

            if (count($chunk) >= 200) {
                DB::table('invoice_lines')->insert($chunk);
                $chunk = [];
            }
        }
        if ($chunk) {
            DB::table('invoice_lines')->insert($chunk);
        }
        $this->line("  → {$inserted} بند");
    }

    private function migratePurchaseOrders($legacy): void
    {
        $rows = $legacy->table('purchase_orders')->get();
        $this->info("أوامر شراء: {$rows->count()}");
        $inserted = 0;

        foreach ($rows as $r) {
            if (DB::table('purchase_orders')->where('id', $r->id)->exists()) {
                continue;
            }

            DB::table('purchase_orders')->insert([
                'id' => $r->id,
                'supplier_id' => $r->supplier_id,
                'legacy_po_no' => $r->legacy_po_no,
                'document_date' => $r->document_date,
                'due_date' => $r->due_date,
                'currency_code' => $r->currency_code,
                'discount_amount' => $r->discount_amount,
                'total_amount' => $r->total_amount,
                'notes' => $r->notes,
                'status' => $r->status,
                'recorded_by_user_id' => $this->staffMap[$r->recorded_by_staff_id] ?? null,
                'source_row_json' => $r->source_row_json,
                'deleted_at' => $r->is_deleted ? $r->updated_at : null,
                'created_at' => $r->created_at,
                'updated_at' => $r->updated_at,
            ]);
            $inserted++;
        }
        $this->line("  → {$inserted} أمر شراء");
    }

    private function migratePurchaseOrderLines($legacy): void
    {
        $rows = $legacy->table('purchase_order_lines')->get();
        $this->info("بنود أوامر شراء: {$rows->count()}");
        $inserted = 0;
        $chunk = [];

        foreach ($rows as $r) {
            if (DB::table('purchase_order_lines')->where('id', $r->id)->exists()) {
                continue;
            }

            $chunk[] = [
                'id' => $r->id,
                'purchase_order_id' => $r->purchase_order_id,
                'line_order' => $r->line_order,
                'title' => $r->title,
                'description' => $r->description,
                'unit_price' => $r->unit_price,
                'quantity' => $r->quantity,
                'line_total' => $r->line_total,
                'source_row_json' => $r->source_row_json,
                'created_at' => $r->created_at,
                'updated_at' => $r->updated_at,
            ];
            $inserted++;

            if (count($chunk) >= 200) {
                DB::table('purchase_order_lines')->insert($chunk);
                $chunk = [];
            }
        }
        if ($chunk) {
            DB::table('purchase_order_lines')->insert($chunk);
        }
        $this->line("  → {$inserted} بند");
    }

    private function migrateClientPayments($legacy): void
    {
        $rows = $legacy->table('client_payments')->get();
        $this->info("دفعات عملاء: {$rows->count()}");
        $inserted = 0;

        foreach ($rows as $r) {
            if (DB::table('client_payments')->where('id', $r->id)->exists()) {
                continue;
            }

            DB::table('client_payments')->insert([
                'id' => $r->id,
                'client_id' => $r->client_id,
                'amount' => $r->amount,
                'currency_code' => $r->currency_code,
                'paid_at' => $r->paid_at,
                'method' => $r->method,
                'bank_reference' => $r->bank_reference,
                'notes' => $r->notes,
                'recorded_by_user_id' => $this->staffMap[$r->recorded_by_staff_id] ?? null,
                'deleted_at' => $r->is_deleted ? $r->updated_at : null,
                'created_at' => $r->created_at,
                'updated_at' => $r->updated_at,
            ]);
            $inserted++;
        }
        $this->line("  → {$inserted} دفعة");
    }

    private function migrateSupplierPayments($legacy): void
    {
        $rows = $legacy->table('supplier_payments')->get();
        $this->info("دفعات موردين: {$rows->count()}");
        $inserted = 0;

        foreach ($rows as $r) {
            if (DB::table('supplier_payments')->where('id', $r->id)->exists()) {
                continue;
            }

            DB::table('supplier_payments')->insert([
                'id' => $r->id,
                'supplier_id' => $r->supplier_id,
                'amount' => $r->amount,
                'currency_code' => $r->currency_code,
                'paid_at' => $r->paid_at,
                'method' => $r->method,
                'bank_reference' => $r->bank_reference,
                'notes' => $r->notes,
                'recorded_by_user_id' => $this->staffMap[$r->recorded_by_staff_id] ?? null,
                'deleted_at' => $r->is_deleted ? $r->updated_at : null,
                'created_at' => $r->created_at,
                'updated_at' => $r->updated_at,
            ]);
            $inserted++;
        }
        $this->line("  → {$inserted} دفعة");
    }

    private function migrateExpenses($legacy): void
    {
        $rows = $legacy->table('expenses')->get();
        $this->info("مصروفات: {$rows->count()}");
        $inserted = 0;

        foreach ($rows as $r) {
            if (DB::table('expenses')->where('id', $r->id)->exists()) {
                continue;
            }

            DB::table('expenses')->insert([
                'id' => $r->id,
                'description' => $r->description,
                'amount' => $r->amount,
                'currency_code' => $r->currency_code,
                'expense_date' => $r->expense_date,
                'notes' => $r->notes,
                'recorded_by_user_id' => $this->staffMap[$r->recorded_by_staff_id] ?? null,
                'source_row_json' => $r->source_row_json,
                'deleted_at' => $r->is_deleted ? $r->updated_at : null,
                'created_at' => $r->created_at,
                'updated_at' => $r->updated_at,
            ]);
            $inserted++;
        }
        $this->line("  → {$inserted} مصروف");
    }

    private function migrateIncomeEntriesAsClientPayments($legacy): void
    {
        $rows = $legacy->table('income_entries')->get();
        $this->info("إيرادات قديمة (تُرحَّل كدفعات عملاء): {$rows->count()}");
        $poolId = $this->ensureMigratedIncomePoolClientId();
        $inserted = 0;

        foreach ($rows as $r) {
            $ref = 'migrated-income-entry:'.$r->id;
            if (DB::table('client_payments')->where('bank_reference', $ref)->exists()) {
                continue;
            }

            $d = $r->income_date ?? $r->created_at;
            try {
                $paidAt = Carbon::parse($d)->startOfDay()->toDateTimeString();
            } catch (\Throwable) {
                $paidAt = now()->toDateTimeString();
            }

            $lines = ['ترحيل من جدول income_entries القديم (مدمج مع دفعات العملاء).'];
            if (! empty($r->description)) {
                $lines[] = 'الوصف: '.$r->description;
            }
            if (! empty($r->notes)) {
                $lines[] = 'ملاحظات: '.$r->notes;
            }

            DB::table('client_payments')->insert([
                'client_id' => $poolId,
                'amount' => $r->amount,
                'currency_code' => $r->currency_code ?? 'ILS',
                'paid_at' => $paidAt,
                'method' => null,
                'bank_reference' => $ref,
                'notes' => implode("\n", $lines),
                'recorded_by_user_id' => $this->staffMap[$r->recorded_by_staff_id] ?? null,
                'deleted_at' => $r->is_deleted ? $r->updated_at : null,
                'created_at' => $r->created_at ?? now(),
                'updated_at' => $r->updated_at ?? now(),
            ]);
            $inserted++;
        }
        $this->line("  → {$inserted} دفعة عميل (من income_entries)");
    }

    private function ensureMigratedIncomePoolClientId(): int
    {
        $existing = DB::table('clients')->where('legacy_match_key', 'migrated:income:pool')->whereNull('deleted_at')->value('id');
        if ($existing) {
            return (int) $existing;
        }

        return (int) DB::table('clients')->insertGetId([
            'legacy_number' => 'migrated-pool-income',
            'legacy_match_key' => 'migrated:income:pool',
            'business_name' => 'دفعات نقدية عامة (ترحيل من income_entries)',
            'first_name' => null,
            'last_name' => null,
            'email' => null,
            'phone_primary' => null,
            'phone_secondary' => null,
            'address_line1' => null,
            'address_line2' => null,
            'city' => null,
            'state_region' => null,
            'postal_code' => null,
            'country_code' => null,
            'notes' => 'يُنشأ عند أمر app:migrate-legacy لتجميع صفوف income_entries دون عميل محدد.',
            'assigned_user_id' => null,
            'source_row_json' => null,
            'deleted_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function migrateLegacyCatalog($legacy): void
    {
        $products = $legacy->table('legacy_catalog_products')->get();
        $projects = $legacy->table('legacy_catalog_projects')->get();
        $this->info("كتالوج قديم: {$products->count()} + {$projects->count()}");

        foreach ($products as $r) {
            if (! DB::table('legacy_catalog_products')->where('id', $r->id)->exists()) {
                DB::table('legacy_catalog_products')->insert([
                    'id' => $r->id,
                    'payload_json' => $r->payload_json,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
        foreach ($projects as $r) {
            if (! DB::table('legacy_catalog_projects')->where('id', $r->id)->exists()) {
                DB::table('legacy_catalog_projects')->insert([
                    'id' => $r->id,
                    'payload_json' => $r->payload_json,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
        $this->line("  → {$products->count()} منتج + {$projects->count()} مشروع");
    }

    private function migrateImportAudit($legacy): void
    {
        $rows = $legacy->table('import_audit')->get();
        $this->info("سجل استيراد: {$rows->count()}");

        foreach ($rows as $r) {
            if (! DB::table('import_audit')->where('id', $r->id)->exists()) {
                DB::table('import_audit')->insert([
                    'id' => $r->id,
                    'batch_name' => $r->batch_name,
                    'source' => $r->source,
                    'notes' => $r->notes,
                    'created_at' => $r->created_at,
                ]);
            }
        }
        $this->line("  → {$rows->count()} سجل");
    }

    private function cleanNotes(?string $notes): ?string
    {
        if (! $notes) {
            return null;
        }
        // تجاهل الملاحظات التقنية من النظام القديم كـ "CreditLimitAmount=0 CreditLimitPeriod=0"
        if (str_contains($notes, 'CreditLimit') || str_contains($notes, '=0')) {
            return null;
        }
        $trimmed = trim($notes);

        return $trimmed !== '' ? $trimmed : null;
    }

    private function truncateAll(): void
    {
        DB::statement('PRAGMA foreign_keys = OFF');
        foreach ([
            'import_audit', 'legacy_catalog_projects', 'legacy_catalog_products',
            'income_entries', 'expenses',
            'supplier_payments', 'client_payments',
            'purchase_order_lines', 'purchase_orders',
            'invoice_lines', 'invoices',
            'supplier_contacts', 'suppliers',
            'client_contacts', 'clients',
        ] as $table) {
            DB::table($table)->delete();
        }
        DB::table('users')->where('email', 'like', 'staff%@profilemedia.local')->delete();
        DB::statement('PRAGMA foreign_keys = ON');
    }

    private function printSummary(): void
    {
        $this->table(
            ['الجدول', 'العدد'],
            collect([
                'users', 'clients', 'client_contacts',
                'suppliers', 'supplier_contacts',
                'invoices', 'invoice_lines',
                'purchase_orders', 'purchase_order_lines',
                'client_payments', 'supplier_payments',
                'expenses',
            ])->map(fn ($t) => [$t, number_format(DB::table($t)->count())])
        );
    }
}
