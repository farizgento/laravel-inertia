<?php

use App\Models\PekerjaanOh;
use App\Models\Peminjaman;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Kolom "pekerjaan" berisi nama pekerjaan overhaul sebagai teks dan
     * bertipe CLOB di Oracle, sehingga tidak bisa dipakai sebagai kunci
     * pencocokan (CLOB tidak bisa dibandingkan dengan "="). Relasi ke pekerjaan
     * overhaul karena itu disimpan sebagai id tersendiri. Kolom teksnya tetap
     * dipertahankan supaya peminjaman lama yang pekerjaannya diisi bebas tidak
     * kehilangan keterangannya.
     */
    public function up(): void
    {
        Schema::table('peminjamans', function (Blueprint $table) {
            $table->unsignedBigInteger('pekerjaan_oh_id')->nullable()->after('pekerjaan');

            $table->foreign('pekerjaan_oh_id', 'peminjaman_pekerjaan_oh_fk')
                ->references('id')
                ->on('pekerjaan_ohs')
                ->nullOnDelete();
        });

        $this->cocokkanDataLama();
    }

    public function down(): void
    {
        Schema::table('peminjamans', function (Blueprint $table) {
            $table->dropForeign('peminjaman_pekerjaan_oh_fk');
            $table->dropColumn('pekerjaan_oh_id');
        });
    }

    /**
     * Peminjaman yang sudah ada dicocokkan sekali berdasarkan nama. Dikerjakan
     * di PHP, bukan SQL, karena perbandingan CLOB berbeda-beda antar database.
     */
    private function cocokkanDataLama(): void
    {
        $perNama = PekerjaanOh::query()
            ->get(['id', 'nama'])
            ->keyBy(fn (PekerjaanOh $pekerjaan) => mb_strtolower(trim((string) $pekerjaan->nama)));

        if ($perNama->isEmpty()) {
            return;
        }

        Peminjaman::query()
            ->whereNull('pekerjaan_oh_id')
            ->chunkById(200, function ($peminjamans) use ($perNama) {
                foreach ($peminjamans as $peminjaman) {
                    $kunci = mb_strtolower(trim((string) $peminjaman->pekerjaan));
                    $cocok = $perNama->get($kunci);

                    if ($cocok) {
                        $peminjaman->forceFill(['pekerjaan_oh_id' => $cocok->id])->saveQuietly();
                    }
                }
            });
    }
};
