<?php

use App\Models\Area;
use App\Models\PekerjaanOh;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pekerjaan_ohs', function (Blueprint $table) {
            // Nullable supaya pekerjaan yang sudah ada tidak menjadi tidak valid.
            // Kewajiban mengisi area ditegakkan saat penyimpanan.
            $table->unsignedBigInteger('area_id')->nullable()->after('id');

            $table->foreign('area_id', 'pekerjaan_oh_area_id_fk')
                ->references('id')
                ->on('areas')
                ->nullOnDelete();
        });

        $this->isiAreaDataLama();
    }

    public function down(): void
    {
        Schema::table('pekerjaan_ohs', function (Blueprint $table) {
            $table->dropForeign('pekerjaan_oh_area_id_fk');
            $table->dropColumn('area_id');
        });
    }

    /**
     * Pekerjaan lama diisi area pembuatnya bila diketahui, sehingga datanya
     * tidak kosong tanpa alasan.
     */
    private function isiAreaDataLama(): void
    {
        PekerjaanOh::query()
            ->whereNull('area_id')
            ->with('creator')
            ->chunkById(200, function ($pekerjaans) {
                foreach ($pekerjaans as $pekerjaan) {
                    $areaId = $pekerjaan->creator?->area_id;

                    if ($areaId && Area::query()->whereKey($areaId)->exists()) {
                        $pekerjaan->forceFill(['area_id' => $areaId])->saveQuietly();
                    }
                }
            });
    }
};
