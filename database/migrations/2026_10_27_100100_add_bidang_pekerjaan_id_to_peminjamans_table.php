<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('peminjamans', function (Blueprint $table) {
            // Dibuat nullable supaya peminjaman yang sudah ada tetap valid dan
            // alur lain yang belum menyertakan bidang tidak langsung gagal.
            // Kewajiban memilih bidang ditegakkan di validasi form.
            $table->unsignedBigInteger('bidang_pekerjaan_id')->nullable()->after('pekerjaan');

            $table->foreign('bidang_pekerjaan_id', 'peminjaman_bidang_fk')
                ->references('id')
                ->on('bidang_pekerjaans')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('peminjamans', function (Blueprint $table) {
            $table->dropForeign('peminjaman_bidang_fk');
            $table->dropColumn('bidang_pekerjaan_id');
        });
    }
};
