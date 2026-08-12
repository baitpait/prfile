<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Business Purpose: Speed up register/due-report queries filtering pending checks by currency and due date.
     */
    public function up(): void
    {
        Schema::table('received_checks', function (Blueprint $table) {
            $table->index(
                ['status', 'currency_code', 'due_date'],
                'received_checks_status_currency_due_index'
            );
        });
    }

    public function down(): void
    {
        Schema::table('received_checks', function (Blueprint $table) {
            $table->dropIndex('received_checks_status_currency_due_index');
        });
    }
};
