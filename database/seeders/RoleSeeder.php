<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            ['key' => Role::KEY_USER, 'name' => 'User'],
            ['key' => Role::KEY_SP_TOOL, 'name' => 'SP Tool'],
            ['key' => Role::KEY_PIC_TOOLS, 'name' => 'PIC Tools'],
            ['key' => Role::KEY_MGR_TOOL, 'name' => 'Mgr Tool'],
            ['key' => Role::KEY_ADMIN, 'name' => 'Admin'],
            ['key' => Role::KEY_SUPER_ADMIN, 'name' => 'Super Admin'],
        ];

        foreach ($roles as $role) {
            Role::firstOrCreate(
                ['key' => $role['key']],
                ['name' => $role['name']]
            );
        }
    }
}
