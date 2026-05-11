<?php

namespace Database\Seeders;

use App\Models\Area;
use Illuminate\Database\Seeder;

class AreaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $areas = [
            ['name' => 'Area 1.1', 'slug' => 'I.1', 'kode' => 'Area1.1'],
            ['name' => 'Area 1.2', 'slug' => 'I.2', 'kode' => 'Area1.2'],
            ['name' => 'UPHK', 'slug' => 'uphk', 'kode' => 'UPHK'],
            ['name' => 'Area 2.1', 'slug' => 'II.1', 'kode' => 'Area2.1'],
            ['name' => 'Area 2.2', 'slug' => 'II.2', 'kode' => 'Area2.2'],
            ['name' => 'Area 2.3', 'slug' => 'II.3', 'kode' => 'Area2.3'],
            ['name' => 'Area 3.1', 'slug' => 'III.1', 'kode' => 'Area3.1'],
            ['name' => 'Area 3.2', 'slug' => 'III.2', 'kode' => 'Area3.2'],
            ['name' => 'Area 3.3', 'slug' => 'III.3', 'kode' => 'Area3.3'],
            ['name' => 'KS TUBUN', 'slug' => 'kstubun', 'kode' => 'KSTUBUN'],
        ];

        foreach ($areas as $area) {
            Area::updateOrCreate(
                ['name' => $area['name']],
                [
                    'slug' => $area['slug'],
                    'kode' => $area['kode'],
                ]
            );
        }
    }
}
