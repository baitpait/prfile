<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->string('employee_code', 32)->nullable()->unique();
            $table->string('full_name');
            $table->string('phone_primary', 30)->nullable();
            $table->string('phone_secondary', 30)->nullable();
            $table->string('email')->nullable();
            $table->string('national_id', 32)->nullable();
            $table->string('job_title')->nullable();
            $table->string('department')->nullable();
            $table->date('hire_date')->nullable();
            $table->date('termination_date')->nullable();
            $table->decimal('base_salary_amount', 15, 4)->default(0);
            $table->char('base_salary_currency', 3)->default('ILS');
            $table->string('pay_frequency', 16)->default('monthly');
            $table->string('bank_name')->nullable();
            $table->string('bank_account')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('recorded_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['is_active', 'full_name']);
        });

        Schema::create('salary_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('period_year');
            $table->unsignedTinyInteger('period_month');
            $table->decimal('base_amount', 15, 4)->default(0);
            $table->decimal('bonus_amount', 15, 4)->default(0);
            $table->decimal('deduction_amount', 15, 4)->default(0);
            $table->decimal('net_amount', 15, 4)->default(0);
            $table->char('currency_code', 3);
            $table->date('paid_at')->nullable();
            $table->string('method', 16)->nullable();
            $table->string('bank_reference')->nullable();
            $table->string('status', 16)->default('draft');
            $table->text('notes')->nullable();
            $table->foreignId('recorded_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();

            $table->unique(
                ['employee_id', 'period_year', 'period_month', 'currency_code'],
                'salary_payments_employee_period_currency_unique'
            );
            $table->index(['period_year', 'period_month', 'status']);
            $table->index('paid_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('salary_payments');
        Schema::dropIfExists('employees');
    }
};
