<?php

namespace Database\Seeders;

use App\Models\Alat;
use App\Models\Area;
use Illuminate\Database\Seeder;

class AlatSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $alatTemplates = [
            ['nama' => 'ampere meter', 'jenis_alat' => 'alat ukur', 'klasifikasi_alat' => 'elektrikal', 'total_aset' => 10],
            ['nama' => 'test pen', 'jenis_alat' => 'alat ukur', 'klasifikasi_alat' => 'elektrikal', 'total_aset' => 6],
            ['nama' => 'termo meter', 'jenis_alat' => 'alat ukur', 'klasifikasi_alat' => 'mekanikal', 'total_aset' => 8],
            ['nama' => 'ring pass 50 mm', 'jenis_alat' => 'kunci', 'klasifikasi_alat' => 'mekanikal', 'total_aset' => 3],
            ['nama' => 'ring pass 46 mm', 'jenis_alat' => 'kunci', 'klasifikasi_alat' => 'mekanikal', 'total_aset' => 3],
            ['nama' => 'ring pass 42 mm', 'jenis_alat' => 'kunci', 'klasifikasi_alat' => 'mekanikal', 'total_aset' => 3],
            ['nama' => 'ring pass 41 mm', 'jenis_alat' => 'kunci', 'klasifikasi_alat' => 'mekanikal', 'total_aset' => 3],
            ['nama' => 'ring pass 38 mm', 'jenis_alat' => 'kunci', 'klasifikasi_alat' => 'mekanikal', 'total_aset' => 3],
            ['nama' => 'ring pass 36 mm', 'jenis_alat' => 'kunci', 'klasifikasi_alat' => 'mekanikal', 'total_aset' => 3],
            ['nama' => 'ring pass 34 mm', 'jenis_alat' => 'kunci', 'klasifikasi_alat' => 'mekanikal', 'total_aset' => 3],
        ];

        $areaId = Area::orderBy('id')->value('id');
        if (! $areaId) {
            return;
        }

        Alat::unguarded(function () use ($alatTemplates, $areaId) {
            foreach ($alatTemplates as $alat) {
                Alat::firstOrCreate(
                    [
                        'nama' => $alat['nama'],
                        'jenis_alat' => $alat['jenis_alat'],
                        'klasifikasi_alat' => $alat['klasifikasi_alat'],
                        'area_id' => $areaId,
                    ],
                    [
                        'total_aset' => $alat['total_aset'],
                    ]
                );
            }
        });
    }
}
