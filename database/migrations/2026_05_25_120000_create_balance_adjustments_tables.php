<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('client_balance_adjustments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 15, 4);
            $table->char('currency_code', 3);
            $table->date('adjustment_date');
            $table->string('type', 32)->default('settlement_discount');
            $table->string('reason')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('recorded_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['client_id', 'currency_code', 'adjustment_date']);
        });

        Schema::create('supplier_balance_adjustments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 15, 4);
            $table->char('currency_code', 3);
            $table->date('adjustment_date');
            $table->string('type', 32)->default('settlement_discount');
            $table->string('reason')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('recorded_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['supplier_id', 'currency_code', 'adjustment_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_balance_adjustments');
        Schema::dropIfExists('client_balance_adjustments');
    }
};
