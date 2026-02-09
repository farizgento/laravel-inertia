<?php

namespace Database\Seeders;

use App\Models\Area;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AreaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $areas = [
            'Area 1.1',
            'Area 1.2',
            'UPHK',
            'Area 2.1',
            'Area 2.2',
            'Area 2.3',
            'Area 3.1',
            'Area 3.2',
            'Area 3.3',
            'KS TUBUN',
        ];

        foreach ($areas as $name) {
            Area::firstOrCreate(['name' => $name]);
        }
    }
}
