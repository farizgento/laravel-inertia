<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('laporan_alats', function (Blueprint $table) {
            $table->date('tanggal_laporan')->nullable()->after('status');
        });

        // Laporan lama memakai tanggal dibuatnya sebagai tanggal pelaporan.
        DB::table('laporan_alats')
            ->whereNull('tanggal_laporan')
            ->update(['tanggal_laporan' => DB::raw('created_at')]);
    }

    public function down(): void
    {
        Schema::table('laporan_alats', function (Blueprint $table) {
            $table->dropColumn('tanggal_laporan');
        });
    }
};
