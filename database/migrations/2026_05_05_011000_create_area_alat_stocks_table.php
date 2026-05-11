<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('area_alat_stocks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('area_id');
            $table->unsignedBigInteger('alat_id');
            $table->unsignedBigInteger('source_peminjaman_id')->nullable();
            $table->unsignedInteger('qty')->default(0);
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->unique(['area_id', 'alat_id', 'source_peminjaman_id'], 'area_alat_stock_unique_source');
            $table->index(['area_id', 'active']);
            $table->index('alat_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('area_alat_stocks');
    }
};
