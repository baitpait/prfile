<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Business Purpose: Link a received check to an employee salary payment or advance when endorsed.
     */
    public function up(): void
    {
        Schema::table('received_checks', function (Blueprint $table) {
            if (! Schema::hasColumn('received_checks', 'endorsed_employee_id')) {
                $table->foreignId('endorsed_employee_id')
                    ->nullable()
                    ->after('endorsed_at')
                    ->constrained('employees')
                    ->nullOnDelete();
            }
            if (! Schema::hasColumn('received_checks', 'salary_payment_id')) {
                $table->foreignId('salary_payment_id')
                    ->nullable()
                    ->after('endorsed_employee_id')
                    ->constrained('salary_payments')
                    ->nullOnDelete();
                $table->unique('salary_payment_id', 'received_checks_salary_payment_id_unique');
            }
            if (! Schema::hasColumn('received_checks', 'salary_advance_id')) {
                $table->foreignId('salary_advance_id')
                    ->nullable()
                    ->after('salary_payment_id')
                    ->constrained('salary_advances')
                    ->nullOnDelete();
                $table->unique('salary_advance_id', 'received_checks_salary_advance_id_unique');
            }
        });
    }

    public function down(): void
    {
        Schema::table('received_checks', function (Blueprint $table) {
            if (Schema::hasColumn('received_checks', 'salary_advance_id')) {
                $table->dropUnique('received_checks_salary_advance_id_unique');
                $table->dropConstrainedForeignId('salary_advance_id');
            }
            if (Schema::hasColumn('received_checks', 'salary_payment_id')) {
                $table->dropUnique('received_checks_salary_payment_id_unique');
                $table->dropConstrainedForeignId('salary_payment_id');
            }
            if (Schema::hasColumn('received_checks', 'endorsed_employee_id')) {
                $table->dropConstrainedForeignId('endorsed_employee_id');
            }
        });
    }
};
