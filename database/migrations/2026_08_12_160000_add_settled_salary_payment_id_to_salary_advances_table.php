<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Business Purpose: Track which salary payment settled an advance when deducted from payroll.
     */
    public function up(): void
    {
        Schema::table('salary_advances', function (Blueprint $table) {
            if (! Schema::hasColumn('salary_advances', 'settled_salary_payment_id')) {
                $table->foreignId('settled_salary_payment_id')
                    ->nullable()
                    ->after('status')
                    ->constrained('salary_payments')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('salary_advances', function (Blueprint $table) {
            if (Schema::hasColumn('salary_advances', 'settled_salary_payment_id')) {
                $table->dropConstrainedForeignId('settled_salary_payment_id');
            }
        });
    }
};
