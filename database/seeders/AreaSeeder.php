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
            ['name' => 'Area 1.1', 'slug' => '1.1'],
            ['name' => 'Area 1.2', 'slug' => '1.2'],
            ['name' => 'UPHK', 'slug' => 'uphk'],
            ['name' => 'Area 2.1', 'slug' => '2.1'],
            ['name' => 'Area 2.2', 'slug' => '2.2'],
            ['name' => 'Area 2.3', 'slug' => '2.3'],
            ['name' => 'Area 3.1', 'slug' => '3.1'],
            ['name' => 'Area 3.2', 'slug' => '3.2'],
            ['name' => 'Area 3.3', 'slug' => '3.3'],
            ['name' => 'KS TUBUN', 'slug' => 'kstubun'],
        ];

        foreach ($areas as $area) {
            Area::updateOrCreate(
                ['name' => $area['name']],
                ['slug' => $area['slug']]
            );
        }
    }
}
