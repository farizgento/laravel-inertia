<?php

namespace Database\Seeders;

use App\Models\Area;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Akun super admin statis yang tidak bergantung pada LDAP. Dipakai sebagai
 * jalur masuk cadangan ketika direktori tidak dapat dihubungi.
 *
 * Jalankan dengan: php artisan db:seed --class=SuperAdminSeeder
 */
class SuperAdminSeeder extends Seeder
{
    private const USERNAME = 'super.trl';

    private const NAMA = 'TOOLS & RLA';

    private const EMAIL = 'super.trl@iptools.local';

    private const KODE_AREA = 'TRLA';

    public function run(): void
    {
        $role = Role::query()->firstOrCreate(
            ['key' => Role::KEY_SUPER_ADMIN],
            ['name' => 'Super Admin']
        );

        // Areanya mengikuti TRLA bila sudah ada; bila belum, akun tetap dibuat
        // tanpa area supaya seeder ini bisa dijalankan sendirian.
        $area = Area::query()->where('kode', self::KODE_AREA)->first();

        // Kata sandi bisa ditimpa lewat env SUPER_ADMIN_PASSWORD.
        $password = (string) env('SUPER_ADMIN_PASSWORD', 'manusiasuper');

        $user = User::query()->updateOrCreate(
            ['username' => self::USERNAME],
            [
                'name' => self::NAMA,
                'email' => self::EMAIL,
                'password' => Hash::make($password),
                'role_id' => $role->id,
                'area_id' => $area?->id,
            ]
        );

        $this->command?->info("Akun super admin statis siap: {$user->username} (".($area?->name ?? 'tanpa area').').');
    }
}
