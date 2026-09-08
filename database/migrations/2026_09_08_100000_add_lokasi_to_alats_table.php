<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lokasi penyimpanan fisik alat di dalam area, mis. "Gudang A - Rak 3".
 * Sebelumnya field "lokasi" pada respons API hanya diisi nama area, sehingga
 * tidak ada tempat mencatat posisi rak/gudang alat.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('alats', function (Blueprint $table) {
            $table->string('lokasi', 255)->nullable()->after('klasifikasi_alat');
        });
    }

    public function down(): void
    {
        Schema::table('alats', function (Blueprint $table) {
            $table->dropColumn('lokasi');
        });
    }
};
