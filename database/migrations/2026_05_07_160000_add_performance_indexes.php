<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('peminjamans', function (Blueprint $table) {
            $table->index(['area_id', 'status'], 'pem_area_stat_idx');
            $table->index(['user_id', 'status'], 'pem_user_stat_idx');
            $table->index(['area_id', 'kategori', 'status'], 'pem_area_kat_stat_idx');
            $table->index(['requester_area_id', 'kategori', 'status'], 'pem_req_kat_stat_idx');
        });

        Schema::table('peminjaman_items', function (Blueprint $table) {
            $table->index(['peminjaman_id', 'approved_qty'], 'pemi_pem_appr_idx');
            $table->index(['alat_id', 'peminjaman_id'], 'pemi_alat_pem_idx');
        });

        Schema::table('alats', function (Blueprint $table) {
            $table->index(['area_id', 'nama'], 'alat_area_nama_idx');
            $table->index(['area_id', 'klasifikasi_alat'], 'alat_area_klas_idx');
        });

        Schema::table('laporan_alats', function (Blueprint $table) {
            $table->index(['status', 'kategori'], 'lap_stat_kat_idx');
        });
    }

    public function down(): void
    {
        Schema::table('laporan_alats', function (Blueprint $table) {
            $table->dropIndex('lap_stat_kat_idx');
        });

        Schema::table('alats', function (Blueprint $table) {
            $table->dropIndex('alat_area_nama_idx');
            $table->dropIndex('alat_area_klas_idx');
        });

        Schema::table('peminjaman_items', function (Blueprint $table) {
            $table->dropIndex('pemi_pem_appr_idx');
            $table->dropIndex('pemi_alat_pem_idx');
        });

        Schema::table('peminjamans', function (Blueprint $table) {
            $table->dropIndex('pem_area_stat_idx');
            $table->dropIndex('pem_user_stat_idx');
            $table->dropIndex('pem_area_kat_stat_idx');
            $table->dropIndex('pem_req_kat_stat_idx');
        });
    }
};
