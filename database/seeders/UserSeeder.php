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
        $area = Area::firstOrCreate(['name' => 'Area 1']);
        $roles = Role::all();

        foreach ($roles as $role) {
            $roleLabel = Str::title(str_replace('_', ' ', $role->key));
            $email = $role->key . '@example.com';

            User::firstOrCreate(
                ['email' => $email],
                [
                    'name' => $roleLabel . ' User',
                    'password' => 'password',
                    'role_id' => $role->id,
                    'area_id' => $area->id,
                ]
            );
        }
    }
}
