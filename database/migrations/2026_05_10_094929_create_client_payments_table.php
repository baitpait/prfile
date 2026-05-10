<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('client_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->restrictOnDelete();
            $table->decimal('amount', 15, 4);
            $table->string('currency_code', 3)->default('ILS');
            $table->timestamp('paid_at');
            $table->string('method')->nullable();
            $table->string('bank_reference')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('recorded_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['client_id', 'paid_at']);
            $table->index(['client_id', 'currency_code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_payments');
    }
};
