<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Business Purpose: Track physical checks received from clients (lifecycle before endorsement).
     */
    public function up(): void
    {
        Schema::create('received_checks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->restrictOnDelete();
            $table->foreignId('client_payment_id')->unique()->constrained('client_payments')->restrictOnDelete();
            $table->string('bank_name');
            $table->string('drawer_name');
            $table->string('check_number', 64);
            $table->date('due_date');
            $table->decimal('amount', 15, 4);
            $table->char('currency_code', 3)->default('ILS');
            $table->string('status', 32)->default('pending');
            $table->string('image_path')->nullable();
            $table->timestamp('cleared_at')->nullable();
            $table->timestamp('not_cleared_at')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('recorded_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['status', 'due_date']);
            $table->index(['client_id', 'currency_code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('received_checks');
    }
};
