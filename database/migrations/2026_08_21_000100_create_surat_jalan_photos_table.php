<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('surat_jalan_photos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('surat_jalan_id');
            $table->unsignedInteger('urutan');
            $table->string('disk', 32)->default('local');
            $table->string('path');
            $table->string('original_name')->nullable();
            $table->string('mime')->default('image/jpeg');
            $table->unsignedBigInteger('size')->nullable();
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->timestamps();

            $table->foreign('surat_jalan_id', 'sj_photo_document_fk')
                ->references('id')
                ->on('surat_jalan')
                ->cascadeOnDelete();
            $table->unique(['surat_jalan_id', 'urutan'], 'sj_photo_order_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('surat_jalan_photos');
    }
};
