<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('alats', function (Blueprint $table) {
            // Kebutuhan maksimal alat. Selisihnya terhadap total aset dihitung
            // saat ditampilkan supaya tidak pernah basi ketika aset berubah.
            $table->unsignedBigInteger('max_demand')->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('alats', function (Blueprint $table) {
            $table->dropColumn('max_demand');
        });
    }
};
