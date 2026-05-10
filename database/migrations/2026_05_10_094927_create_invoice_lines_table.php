<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('line_order')->default(0);
            $table->string('title');
            $table->text('description')->nullable();
            $table->decimal('unit_price', 15, 4);
            $table->decimal('quantity', 15, 4)->default(1);
            $table->decimal('line_total', 15, 4);
            $table->json('source_row_json')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_lines');
    }
};
