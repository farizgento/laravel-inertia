<?php

namespace Database\Seeders;

use App\Models\Area;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    private const DEFAULT_PASSWORD = 'Password26';
    private const SUPER_ADMIN_PASSWORD = 'TRLA26!';
    private const TRLA_SLUG = 'trla';

    private const DUMMY_AREA_ROLE_KEYS = [
        Role::KEY_USER,
        Role::KEY_ADMIN,
    ];

    private const SUPER_ADMIN_ACCOUNT = [
        'name' => 'Super Admin TRLA',
        'username' => 'super_admin.trla',
        'email' => 'fariz.aminullah@plnindonesiapower.co.id',
    ];

    private const PIC_AREA_ACCOUNTS = [
        'I.1' => [
            Role::KEY_PIC_TOOL => ['name' => 'ASWIN BINSAR YOHANES', 'username' => 'aswin.binsar', 'email' => 'aswin.binsar@plnindonesiapower.co.id'],
            Role::KEY_MGR_TOOL => ['name' => 'WAHYU TRI ATMOJO', 'username' => 'wahyu.atmojo', 'email' => 'wahyu.atmojo@plnindonesiapower.co.id'],
            Role::KEY_SP_TOOL => ['name' => 'ROMDHANI', 'username' => 'romdhani', 'email' => 'romdhani@plnindonesiapower.co.id'],
        ],
        'I.2' => [
            Role::KEY_PIC_TOOL => ['name' => 'YAN SANDY PRATAMA', 'username' => 'yan.sandi', 'email' => 'yan.sandi@plnindonesiapower.co.id'],
            Role::KEY_MGR_TOOL => ['name' => 'M. RENDO AVERYANTO', 'username' => 'rendo.averyanto', 'email' => 'rendo.averyanto@plnindonesiapower.co.id'],
            Role::KEY_SP_TOOL => ['name' => 'ARGA BAYU INDRAWAN', 'username' => 'arga.bayu', 'email' => 'arga.bayu@plnindonesiapower.co.id'],
        ],
        'UPHK' => [
            Role::KEY_PIC_TOOL => ['name' => 'HAJAR ASWAT', 'username' => 'hajar.aswat', 'email' => 'hajar.aswat@plnindonesiapower.co.id'],
            Role::KEY_MGR_TOOL => ['name' => 'FANDY PUTRA', 'username' => 'fandy', 'email' => 'fandy@plnindonesiapower.co.id'],
            Role::KEY_SP_TOOL => ['name' => 'IQBAL ABDI PUTRA', 'username' => 'iqbal.ap', 'email' => 'iqbal.ap@plnindonesiapower.co.id'],
        ],
        'II.1' => [
            Role::KEY_PIC_TOOL => ['name' => 'DEVON ZAMPUTRA', 'username' => 'devon.zamputra', 'email' => 'devon.zamputra@plnindonesiapower.co.id'],
            Role::KEY_MGR_TOOL => ['name' => 'TRI PAPRI HANDONO', 'username' => 'tri.papri', 'email' => 'tri.papri@plnindonesiapower.co.id'],
            Role::KEY_SP_TOOL => ['name' => 'EDO ANGGA RADITA', 'username' => 'edo.angga', 'email' => 'edo.angga@plnindonesiapower.co.id'],
        ],
        'II.2' => [
            Role::KEY_PIC_TOOL => ['name' => 'HANDI PRASTIYO', 'username' => 'handi.prastiyo', 'email' => 'handi.prastiyo@plnindonesiapower.co.id'],
            Role::KEY_MGR_TOOL => ['name' => 'RURY MANGARA BATUBARA', 'username' => 'ruri.mangara', 'email' => 'ruri.mangara@plnindonesiapower.co.id'],
            Role::KEY_SP_TOOL => ['name' => 'AMIR HAMZAH', 'username' => 'amir.hzubh2', 'email' => 'amir.hzubh2@plnindonesiapower.co.id'],
        ],
        'II.3' => [
            Role::KEY_PIC_TOOL => ['name' => 'TRI ARDIANTO', 'username' => 'tri.ardianto', 'email' => 'tri.ardianto@indonesiapower.co.id'],
            Role::KEY_MGR_TOOL => ['name' => 'AGUNG SUWASONO', 'username' => 'agung.suwasono', 'email' => 'agung.suwasono@plnindonesiapower.co.id'],
            Role::KEY_SP_TOOL => ['name' => 'ERIX AGUS NUGROHO', 'username' => 'erix.nugroho', 'email' => 'erix.nugroho@plnindonesiapower.co.id'],
        ],
        'III.1' => [
            Role::KEY_PIC_TOOL => ['name' => 'RUSDYATMOKO PAMUNGKAS', 'username' => 'rusdiyatmoko.pamungkas', 'email' => 'rusdiyatmoko.pamungkas@plnindonesiapower.co.id'],
            Role::KEY_MGR_TOOL => ['name' => 'MOCHAMAD RAVI HIDAYAT', 'username' => 'mochamad.ravi', 'email' => 'mochamad.ravi@plnindonesiapower.co.id'],
            Role::KEY_SP_TOOL => ['name' => 'PANDE GEDE WAHYUDI', 'username' => 'pande.wahyudi', 'email' => 'pande.wahyudi@plnindonesiapower.co.id'],
        ],
        'III.2' => [
            Role::KEY_PIC_TOOL => ['name' => 'AAN PRATAMA (IPS)', 'username' => 'aan.pratama', 'email' => 'aan.pratama@plnindonesiapower.co.id'],
            Role::KEY_MGR_TOOL => ['name' => 'HANDONO', 'username' => 'handono', 'email' => 'handono@plnindonesiapower.co.id'],
            Role::KEY_SP_TOOL => ['name' => 'LUKMAN FAJAR HIDAYAT', 'username' => 'lukman.hidayat', 'email' => 'lukman.hidayat@plnindonesiapower.co.id'],
        ],
        'III.3' => [
            Role::KEY_PIC_TOOL => ['name' => 'RYAN NURDIANTO', 'username' => 'ryan.nurdianto', 'email' => 'ryan.nurdianto@indonesiapower.co.id'],
            Role::KEY_MGR_TOOL => ['name' => 'AMRI KISWARA', 'username' => 'amri.kiswara', 'email' => 'amri.kiswara@plnindonesiapower.co.id'],
            Role::KEY_SP_TOOL => ['name' => 'DIMAS HERJUNO', 'username' => 'dimas.herjuno', 'email' => 'dimas.herjuno@plnindonesiapower.co.id'],
        ],
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $areas = Area::query()->orderBy('name')->get();
        $areasBySlug = $areas->keyBy(fn (Area $area) => $this->areaSlug($area));
        $roles = Role::query()->get()->keyBy('key');

        if ($areas->isEmpty() || $roles->isEmpty()) {
            return;
        }

        foreach ($areas as $area) {
            foreach (self::DUMMY_AREA_ROLE_KEYS as $roleKey) {
                $role = $roles->get($roleKey);

                if (! $role) {
                    continue;
                }

                $this->seedDummyAreaRoleUser($area, $role);
            }
        }

        foreach (self::PIC_AREA_ACCOUNTS as $areaKey => $accounts) {
            $area = $areasBySlug[strtolower($areaKey)] ?? null;

            if (! $area) {
                continue;
            }

            foreach ($accounts as $roleKey => $account) {
                $role = $roles->get($roleKey);

                if (! $role) {
                    continue;
                }

                $this->seedAreaRoleUser(
                    area: $area,
                    role: $role,
                    name: $account['name'],
                    username: $account['username'],
                    email: $account['email'],
                );
            }
        }

        $trlaArea = $areasBySlug[self::TRLA_SLUG] ?? null;
        if (! $trlaArea) {
            return;
        }

        $role = $roles->get(Role::KEY_SUPER_ADMIN);

        if ($role) {
            $this->seedUser(
                username: self::SUPER_ADMIN_ACCOUNT['username'],
                name: self::SUPER_ADMIN_ACCOUNT['name'],
                email: self::SUPER_ADMIN_ACCOUNT['email'],
                role: $role,
                area: $trlaArea,
                password: self::SUPER_ADMIN_PASSWORD,
            );
        }
    }

    private function seedDummyAreaRoleUser(Area $area, Role $role): void
    {
        $this->seedAreaRoleUser(
            area: $area,
            role: $role,
            name: "{$role->name} {$area->name}",
            email: "{$role->key}.{$this->areaSlug($area)}@example.com",
        );
    }

    private function seedAreaRoleUser(Area $area, Role $role, string $name, string $email, ?string $username = null): void
    {
        $this->seedUser(
            username: $username ?? "{$role->key}.{$this->areaSlug($area)}",
            name: $name,
            email: $email,
            role: $role,
            area: $area,
        );
    }

    private function seedUser(
        string $username,
        string $name,
        string $email,
        Role $role,
        Area $area,
        string $password = self::DEFAULT_PASSWORD
    ): void {
        $user = User::query()
            ->where('username', $username)
            ->orWhere('email', $email)
            ->first();

        $payload = [
            'name' => $name,
            'username' => $username,
            'email' => $email,
            'password' => $password,
            'role_id' => $role->id,
            'area_id' => $area->id,
        ];

        $user ? $user->update($payload) : User::create($payload);
    }

    private function areaSlug(Area $area): string
    {
        return strtolower(trim((string) $area->slug));
    }
}
