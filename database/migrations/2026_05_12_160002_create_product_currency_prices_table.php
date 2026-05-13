<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_currency_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->char('currency_code', 3);
            $table->decimal('service_cost_price', 15, 4)->nullable();
            $table->decimal('min_sale_price', 15, 4)->nullable();
            $table->decimal('sale_price', 15, 4)->nullable();
            $table->timestamps();

            $table->unique(['product_id', 'currency_code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_currency_prices');
    }
};
