<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('laporan_alat_photos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('laporan_alat_id');
            $table->unsignedInteger('urutan')->default(1);
            $table->string('path');
            $table->string('original_name')->nullable();
            $table->string('mime')->nullable();
            $table->unsignedBigInteger('size')->nullable();
            $table->timestamps();

            $table->foreign('laporan_alat_id', 'laporan_photo_laporan_fk')
                ->references('id')
                ->on('laporan_alats')
                ->cascadeOnDelete();
            $table->index('laporan_alat_id', 'laporan_photo_laporan_idx');
        });

        // Foto tunggal pada laporan lama dipindahkan menjadi foto pertama.
        DB::table('laporan_alats')
            ->whereNotNull('path')
            ->orderBy('id')
            ->chunkById(500, function ($laporans) {
                $baris = [];

                foreach ($laporans as $laporan) {
                    $baris[] = [
                        'laporan_alat_id' => $laporan->id,
                        'urutan' => 1,
                        'path' => $laporan->path,
                        'original_name' => $laporan->original_name,
                        'mime' => $laporan->mime,
                        'size' => $laporan->size,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }

                if ($baris) {
                    DB::table('laporan_alat_photos')->insert($baris);
                }
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('laporan_alat_photos');
    }
};
