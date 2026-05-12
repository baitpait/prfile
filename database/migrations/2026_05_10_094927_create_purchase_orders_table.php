<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained()->restrictOnDelete();
            $table->string('legacy_po_no')->nullable()->unique();
            $table->date('document_date');
            $table->date('due_date')->nullable();
            $table->string('currency_code', 3)->default('ILS');
            $table->decimal('discount_amount', 15, 4)->default(0);
            $table->decimal('total_amount', 15, 4);
            $table->text('notes')->nullable();
            $table->enum('status', ['draft', 'issued', 'void'])->default('issued');
            $table->foreignId('recorded_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->json('source_row_json')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['supplier_id', 'currency_code', 'document_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_orders');
    }
};
