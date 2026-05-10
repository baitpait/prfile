<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('legacy_catalog_products', function (Blueprint $table) {
            $table->id();
            $table->json('payload_json');
            $table->timestamps();
        });

        Schema::create('legacy_catalog_projects', function (Blueprint $table) {
            $table->id();
            $table->json('payload_json');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('legacy_catalog_projects');
        Schema::dropIfExists('legacy_catalog_products');
    }
};
