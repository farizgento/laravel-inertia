<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\SuperAdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SuperAdminSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_membuat_akun_super_admin_statis(): void
    {
        Area::create(['name' => 'TOOLS & RLA', 'slug' => 'trla', 'kode' => 'TRLA']);

        $this->seed(SuperAdminSeeder::class);

        $user = User::query()->where('username', 'super.trl')->with(['role', 'area'])->firstOrFail();

        $this->assertSame('TOOLS & RLA', $user->name);
        $this->assertSame(Role::KEY_SUPER_ADMIN, $user->role?->key);
        $this->assertSame('TRLA', $user->area?->kode);
        $this->assertTrue(Hash::check('manusiasuper', $user->password));
        // Kata sandi tidak boleh ikut terbawa saat model diubah ke array/JSON.
        $this->assertArrayNotHasKey('password', $user->toArray());
    }

    public function test_seeder_aman_dijalankan_berulang(): void
    {
        Area::create(['name' => 'TOOLS & RLA', 'slug' => 'trla', 'kode' => 'TRLA']);

        $this->seed(SuperAdminSeeder::class);
        $this->seed(SuperAdminSeeder::class);

        $this->assertSame(1, User::query()->where('username', 'super.trl')->count());
    }

    public function test_akun_statis_bisa_login_tanpa_ldap(): void
    {
        Area::create(['name' => 'TOOLS & RLA', 'slug' => 'trla', 'kode' => 'TRLA']);
        $this->seed(SuperAdminSeeder::class);

        $this->postJson('/api/auth/login', [
            'username' => 'super.trl',
            'password' => 'manusiasuper',
        ])
            ->assertOk()
            ->assertJsonPath('user.username', 'super.trl')
            ->assertJsonPath('user.role.key', Role::KEY_SUPER_ADMIN)
            ->assertJsonMissingPath('user.password');
    }

    public function test_akun_statis_menolak_kata_sandi_salah(): void
    {
        Area::create(['name' => 'TOOLS & RLA', 'slug' => 'trla', 'kode' => 'TRLA']);
        $this->seed(SuperAdminSeeder::class);

        $this->postJson('/api/auth/login', [
            'username' => 'super.trl',
            'password' => 'bukan-password',
        ])->assertStatus(401);

        $this->assertGuest();
    }
}
