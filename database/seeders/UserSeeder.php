<?php

namespace Database\Seeders;

use App\Models\Area;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = Role::all();
        $areas = Area::query()->orderBy('name')->get();

        if ($areas->isEmpty()) {
            $areas = collect([Area::firstOrCreate(['name' => 'Area 1.1'])]);
        }

        foreach ($areas as $area) {
            $areaSlug = Str::slug($area->name);

            foreach ($roles as $role) {
                $roleLabel = Str::title(str_replace('_', ' ', $role->key));
                $email = "{$role->key}.{$areaSlug}@example.com";

                User::firstOrCreate(
                    ['email' => $email],
                    [
                        'name' => "{$roleLabel} {$area->name}",
                        'password' => 'password',
                        'role_id' => $role->id,
                        'area_id' => $area->id,
                    ]
                );
            }
        }
    }
}
