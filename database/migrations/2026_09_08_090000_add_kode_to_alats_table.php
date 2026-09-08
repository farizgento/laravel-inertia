<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Sebelumnya kode alat hanya turunan dari id global (mis. TRLA-482), sehingga
 * penomorannya melompat-lompat dan tidak pernah dimulai dari 1 di tiap area.
 * Kolom ini membuat kode menjadi data tersimpan yang bisa diubah pengguna,
 * dengan penomoran berjalan sendiri per area.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('alats', function (Blueprint $table) {
            $table->string('kode', 100)->nullable()->after('id');
        });

        $this->backfillKode();

        Schema::table('alats', function (Blueprint $table) {
            // Kode cukup unik di dalam satu area; dua area berbeda boleh memakai
            // angka yang sama karena awalannya sudah membedakan.
            $table->unique(['area_id', 'kode'], 'alats_area_kode_unique');
        });
    }

    public function down(): void
    {
        Schema::table('alats', function (Blueprint $table) {
            $table->dropUnique('alats_area_kode_unique');
            $table->dropColumn('kode');
        });
    }

    /**
     * Isi kode alat yang sudah ada dengan nomor urut per area, diurutkan dari
     * alat terlama supaya nomornya stabil dan mudah ditebak.
     */
    private function backfillKode(): void
    {
        $areas = DB::table('areas')->select('id', 'kode')->get();

        foreach ($areas as $area) {
            $prefix = trim((string) ($area->kode ?? ''));
            $prefix = $prefix !== '' ? $prefix : 'AREA';

            $ids = DB::table('alats')
                ->where('area_id', $area->id)
                ->orderBy('id')
                ->pluck('id');

            $urutan = 1;
            foreach ($ids as $id) {
                DB::table('alats')
                    ->where('id', $id)
                    ->update(['kode' => $prefix . '-' . $urutan]);
                $urutan++;
            }
        }

        // Alat tanpa area (bila ada) tetap diberi kode agar tidak kosong.
        $tanpaArea = DB::table('alats')->whereNull('area_id')->orderBy('id')->pluck('id');
        $urutan = 1;
        foreach ($tanpaArea as $id) {
            DB::table('alats')->where('id', $id)->update(['kode' => 'AREA-' . $urutan]);
            $urutan++;
        }
    }
};
