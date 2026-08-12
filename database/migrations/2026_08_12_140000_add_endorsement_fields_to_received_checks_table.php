<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Business Purpose: Link a received check to a supplier payment when the check is endorsed (passed to supplier).
     */
    public function up(): void
    {
        Schema::table('received_checks', function (Blueprint $table) {
            if (! Schema::hasColumn('received_checks', 'endorsed_supplier_id')) {
                $table->foreignId('endorsed_supplier_id')
                    ->nullable()
                    ->after('not_cleared_at')
                    ->constrained('suppliers')
                    ->nullOnDelete();
            }
            if (! Schema::hasColumn('received_checks', 'supplier_payment_id')) {
                $table->foreignId('supplier_payment_id')
                    ->nullable()
                    ->after('endorsed_supplier_id')
                    ->constrained('supplier_payments')
                    ->nullOnDelete();
                $table->unique('supplier_payment_id', 'received_checks_supplier_payment_id_unique');
            }
            if (! Schema::hasColumn('received_checks', 'endorsed_at')) {
                $table->timestamp('endorsed_at')->nullable()->after('supplier_payment_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('received_checks', function (Blueprint $table) {
            if (Schema::hasColumn('received_checks', 'supplier_payment_id')) {
                $table->dropUnique('received_checks_supplier_payment_id_unique');
                $table->dropConstrainedForeignId('supplier_payment_id');
            }
            if (Schema::hasColumn('received_checks', 'endorsed_supplier_id')) {
                $table->dropConstrainedForeignId('endorsed_supplier_id');
            }
            if (Schema::hasColumn('received_checks', 'endorsed_at')) {
                $table->dropColumn('endorsed_at');
            }
        });
    }
};
