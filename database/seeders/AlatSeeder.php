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
            ['nama' => 'Ampere Meter', 'total_aset' => 10, 'kategori' => 'listrik', 'kondisi' => 'baik'],
            ['nama' => 'Test Pen', 'total_aset' => 6, 'kategori' => 'listrik', 'kondisi' => 'baik'],
            ['nama' => 'Termo Meter', 'total_aset' => 8, 'kategori' => 'listrik', 'kondisi' => 'baik'],
            ['nama' => 'Ring Pass 50 mm', 'total_aset' => 3, 'kategori' => 'perkakas', 'kondisi' => 'baik'],
            ['nama' => 'Ring Pass 46 mm', 'total_aset' => 3, 'kategori' => 'perkakas', 'kondisi' => 'baik'],
            ['nama' => 'Ring Pass 42 mm', 'total_aset' => 3, 'kategori' => 'perkakas', 'kondisi' => 'baik'],
            ['nama' => 'Ring Pass 41 mm', 'total_aset' => 3, 'kategori' => 'perkakas', 'kondisi' => 'baik'],
            ['nama' => 'Ring Pass 38 mm', 'total_aset' => 3, 'kategori' => 'perkakas', 'kondisi' => 'baik'],
            ['nama' => 'Ring Pass 36 mm', 'total_aset' => 3, 'kategori' => 'perkakas', 'kondisi' => 'baik'],
            ['nama' => 'Ring Pass 34 mm', 'total_aset' => 3, 'kategori' => 'perkakas', 'kondisi' => 'baik'],
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
                        'kategori' => $alat['kategori'],
                        'total_aset' => $alat['total_aset'],
                        'kondisi' => $alat['kondisi'],
                    ]
                );
            }
        });
    }
}
