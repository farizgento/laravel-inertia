<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->renameLegacyPicToolRole();

        $roles = [
            ['key' => Role::KEY_USER, 'name' => 'User'],
            ['key' => Role::KEY_SP_TOOL, 'name' => 'SP Tool'],
            ['key' => Role::KEY_PIC_TOOL, 'name' => 'PIC Tool'],
            ['key' => Role::KEY_MGR_TOOL, 'name' => 'Mgr Tool'],
            ['key' => Role::KEY_ADMIN, 'name' => 'Admin'],
            ['key' => Role::KEY_SUPER_ADMIN, 'name' => 'Super Admin'],
        ];

        foreach ($roles as $role) {
            Role::updateOrCreate(
                ['key' => $role['key']],
                ['name' => $role['name']]
            );
        }
    }

    private function renameLegacyPicToolRole(): void
    {
        $legacyRole = Role::query()->where('key', 'pic_tools')->first();

        if (! $legacyRole) {
            return;
        }

        $currentRole = Role::query()->where('key', Role::KEY_PIC_TOOL)->first();

        if ($currentRole) {
            User::query()
                ->where('role_id', $legacyRole->id)
                ->update(['role_id' => $currentRole->id]);

            $legacyRole->delete();

            return;
        }

        $legacyRole->update([
            'key' => Role::KEY_PIC_TOOL,
            'name' => 'PIC Tool',
        ]);
    }
}
