<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Business Purpose: Allow a payment to target one invoice/PO (full amount) or stay on the party account.
     */
    public function up(): void
    {
        Schema::table('client_payments', function (Blueprint $table) {
            if (! Schema::hasColumn('client_payments', 'invoice_id')) {
                $table->foreignId('invoice_id')
                    ->nullable()
                    ->after('client_id')
                    ->constrained('invoices')
                    ->nullOnDelete();
                $table->index('invoice_id', 'client_payments_invoice_id_idx');
            }
        });

        Schema::table('supplier_payments', function (Blueprint $table) {
            if (! Schema::hasColumn('supplier_payments', 'purchase_order_id')) {
                $table->foreignId('purchase_order_id')
                    ->nullable()
                    ->after('supplier_id')
                    ->constrained('purchase_orders')
                    ->nullOnDelete();
                $table->index('purchase_order_id', 'supplier_payments_purchase_order_id_idx');
            }
        });
    }

    public function down(): void
    {
        Schema::table('client_payments', function (Blueprint $table) {
            if (Schema::hasColumn('client_payments', 'invoice_id')) {
                $table->dropConstrainedForeignId('invoice_id');
            }
        });

        Schema::table('supplier_payments', function (Blueprint $table) {
            if (Schema::hasColumn('supplier_payments', 'purchase_order_id')) {
                $table->dropConstrainedForeignId('purchase_order_id');
            }
        });
    }
};
