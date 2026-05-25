<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('client_balance_adjustments')) {
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

                $table->index(['client_id', 'currency_code', 'adjustment_date'], 'cba_client_cur_date_idx');
            });
        } elseif (! $this->indexExists('client_balance_adjustments', 'cba_client_cur_date_idx')) {
            Schema::table('client_balance_adjustments', function (Blueprint $table): void {
                $table->index(['client_id', 'currency_code', 'adjustment_date'], 'cba_client_cur_date_idx');
            });
        }

        if (! Schema::hasTable('supplier_balance_adjustments')) {
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

                $table->index(['supplier_id', 'currency_code', 'adjustment_date'], 'sba_supplier_cur_date_idx');
            });
        } elseif (! $this->indexExists('supplier_balance_adjustments', 'sba_supplier_cur_date_idx')) {
            Schema::table('supplier_balance_adjustments', function (Blueprint $table): void {
                $table->index(['supplier_id', 'currency_code', 'adjustment_date'], 'sba_supplier_cur_date_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_balance_adjustments');
        Schema::dropIfExists('client_balance_adjustments');
    }

    private function indexExists(string $table, string $indexName): bool
    {
        if (! Schema::hasTable($table)) {
            return false;
        }

        $connection = Schema::getConnection();
        if ($connection->getDriverName() === 'sqlite') {
            return false;
        }

        $database = $connection->getDatabaseName();
        $result = $connection->select(
            'SELECT COUNT(*) AS c FROM information_schema.statistics WHERE table_schema = ? AND table_name = ? AND index_name = ?',
            [$database, $table, $indexName]
        );

        return (int) ($result[0]->c ?? 0) > 0;
    }
};
