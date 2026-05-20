<?php

namespace Database\Seeders;

use App\Models\Area;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    private const DEFAULT_PASSWORD = 'Password26';
    private const TRLA_SLUG = 'trla';

    private const AREA_ROLE_KEYS = [
        Role::KEY_USER,
        Role::KEY_PIC_TOOLS,
        Role::KEY_SP_TOOL,
        Role::KEY_MGR_TOOL,
        Role::KEY_ADMIN,
    ];

    private const SINGLE_ROLE_USERS = [
        Role::KEY_SUPER_ADMIN => [
            'name' => 'Super Admin TRLA',
            'username' => 'super_admin.trla',
            'email' => 'fariz.aminullah@plnindonesiapower.co.id',
        ],
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $areas = Area::query()->orderBy('name')->get()->keyBy(
            fn (Area $area) => $this->resolveAreaSlug($area)
        );
        $roles = Role::query()->get()->keyBy('key');

        if ($areas->isEmpty() || $roles->isEmpty()) {
            return;
        }

        foreach ($areas as $area) {
            foreach (self::AREA_ROLE_KEYS as $roleKey) {
                $role = $roles->get($roleKey);

                if (! $role) {
                    continue;
                }

                $this->seedAreaRoleUser($area, $role);
            }
        }

        $trlaArea = $areas->get(self::TRLA_SLUG);
        if (! $trlaArea) {
            return;
        }

        foreach (self::SINGLE_ROLE_USERS as $roleKey => $account) {
            $role = $roles->get($roleKey);

            if (! $role) {
                continue;
            }

            $this->seedSingleRoleUser(
                area: $trlaArea,
                role: $role,
                name: $account['name'],
                username: $account['username'],
                email: $account['email'],
            );
        }
    }

    private function seedAreaRoleUser(Area $area, Role $role): void
    {
        $areaSlug = $this->resolveAreaSlug($area);

        User::updateOrCreate(
            [
                'username' => "{$role->key}.{$areaSlug}",
            ],
            [
                'name' => "{$role->name} {$area->name}",
                'email' => "{$role->key}.{$areaSlug}@example.com",
                'password' => self::DEFAULT_PASSWORD,
                'role_id' => $role->id,
                'area_id' => $area->id,
            ]
        );
    }

    private function seedSingleRoleUser(Area $area, Role $role, string $name, string $username, string $email): void
    {
        User::query()
            ->where('role_id', $role->id)
            ->where('username', '!=', $username)
            ->delete();

        User::updateOrCreate(
            [
                'username' => $username,
            ],
            [
                'name' => $name,
                'email' => $email,
                'password' => self::DEFAULT_PASSWORD,
                'role_id' => $role->id,
                'area_id' => $area->id,
            ]
        );
    }

    private function resolveAreaSlug(Area $area): string
    {
        $slug = trim((string) $area->slug);
        if ($slug !== '') {
            return strtolower($slug);
        }

        return Str::slug((string) $area->name);
    }
}
