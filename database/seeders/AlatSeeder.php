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
            ['nama' => 'ampere meter', 'total_aset' => 10],
            ['nama' => 'test pen', 'total_aset' => 6],
            ['nama' => 'termo meter', 'total_aset' => 8],
            ['nama' => 'ring pass 50 mm', 'total_aset' => 3],
            ['nama' => 'ring pass 46 mm', 'total_aset' => 3],
            ['nama' => 'ring pass 42 mm', 'total_aset' => 3],
            ['nama' => 'ring pass 41 mm', 'total_aset' => 3],
            ['nama' => 'ring pass 38 mm', 'total_aset' => 3],
            ['nama' => 'ring pass 36 mm', 'total_aset' => 3],
            ['nama' => 'ring pass 34 mm', 'total_aset' => 3],
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
