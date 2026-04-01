<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('laporan_alats', function (Blueprint $table) {
            $table->id();
            $table->string('kategori');
            $table->text('deskripsi')->nullable();
            $table->string('status')->default('Dilaporkan');
            $table->unsignedBigInteger('jumlah')->default(0);
            $table->unsignedBigInteger('alat_id')->nullable();
            $table->foreign('alat_id')
                ->references('id')
                ->on('alats')
                ->nullOnDelete();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
            $table->string('path')->nullable();
            $table->string('original_name')->nullable();
            $table->string('mime')->nullable();
            $table->unsignedBigInteger('size')->nullable();
            $table->timestamps();

            $table->index(['kategori', 'status']);
            $table->index('alat_id');
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('laporan_alats');
    }
};
