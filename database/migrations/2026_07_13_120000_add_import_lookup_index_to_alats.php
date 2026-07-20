<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('alats', function (Blueprint $table) {
            $table->index(['area_id', 'nama', 'jenis_alat', 'klasifikasi_alat'], 'alat_import_lookup_idx');
        });
    }

    public function down(): void
    {
        Schema::table('alats', function (Blueprint $table) {
            $table->dropIndex('alat_import_lookup_idx');
        });
    }
};
