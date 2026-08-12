<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Business Purpose: Track salary advances paid to employees (including check endorsement).
     */
    public function up(): void
    {
        Schema::create('salary_advances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->restrictOnDelete();
            $table->decimal('amount', 15, 4);
            $table->char('currency_code', 3);
            $table->date('paid_at');
            $table->string('method', 16);
            $table->string('bank_reference')->nullable();
            $table->string('status', 16)->default('paid');
            $table->text('notes')->nullable();
            $table->foreignId('recorded_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['employee_id', 'paid_at']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('salary_advances');
    }
};
