<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bidang_pekerjaans', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->timestamps();

            // Nama indeks ditulis manual dan dibuat pendek karena Oracle
            // membatasi panjang identifier.
            $table->unique('nama', 'bidang_pekerjaan_nama_uq');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bidang_pekerjaans');
    }
};
