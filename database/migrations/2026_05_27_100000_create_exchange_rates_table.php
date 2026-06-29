<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exchange_rates', function (Blueprint $table) {
            $table->id();
            $table->date('rate_date');
            $table->char('currency_code', 3);
            $table->decimal('rate_to_ils', 15, 6);
            $table->string('source', 32)->default('BOI');
            $table->timestamps();

            $table->unique(['rate_date', 'currency_code'], 'exchange_rates_date_currency_unique');
            $table->index(['currency_code', 'rate_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exchange_rates');
    }
};
