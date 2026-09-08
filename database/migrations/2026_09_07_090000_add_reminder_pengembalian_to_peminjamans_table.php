<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Menyimpan tanggal terakhir pengingat pengembalian dikirim untuk satu
     * peminjaman. Dipakai sebagai pengaman supaya perintah penjadwalan yang
     * kebetulan berjalan dua kali dalam sehari (atau dijalankan manual saat
     * pengujian) tidak mengirim email ganda ke peminjam.
     */
    public function up(): void
    {
        Schema::table('peminjamans', function (Blueprint $table) {
            $table->date('reminder_pengembalian_terakhir')->nullable()->after('tanggal_kembali');
        });
    }

    public function down(): void
    {
        Schema::table('peminjamans', function (Blueprint $table) {
            $table->dropColumn('reminder_pengembalian_terakhir');
        });
    }
};
