<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Area;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ActivityLogPurgeTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Akun uji memakai kata sandi lokal, meniru akun super admin statis. Dengan
     * begitu verifikasi berjalan lewat hash lokal dan test tidak bergantung pada
     * direktori LDAP.
     */
    private const SANDI = 'sandi-uji-rahasia';

    public function test_hanya_entri_yang_cocok_filter_aksi_yang_terhapus(): void
    {
        ['user' => $user, 'area' => $area] = $this->siapkanSuperAdmin();

        $this->buatLog($area, ['action' => 'create', 'subject_label' => 'Alat A']);
        $this->buatLog($area, ['action' => 'create', 'subject_label' => 'Alat B']);
        $this->buatLog($area, ['action' => 'update', 'subject_label' => 'Alat C']);

        Sanctum::actingAs($user);

        $response = $this->deleteJson('/api/activity-logs?action=create', [
            'password' => self::SANDI,
        ]);

        $response->assertOk();
        $this->assertSame(2, $response->json('deleted'));

        // Yang beraksi update tetap ada, ditambah satu entri pencatat penghapusan.
        $this->assertSame(0, ActivityLog::query()->where('action', 'create')->count());
        $this->assertSame(1, ActivityLog::query()->where('action', 'update')->count());
    }

    public function test_pencarian_mempersempit_penghapusan(): void
    {
        ['user' => $user, 'area' => $area] = $this->siapkanSuperAdmin();

        $this->buatLog($area, ['subject_label' => 'OBENG RING']);
        $this->buatLog($area, ['subject_label' => 'KUNCI INGGRIS']);

        Sanctum::actingAs($user);

        $response = $this->deleteJson('/api/activity-logs?search=OBENG', [
            'password' => self::SANDI,
        ]);

        $response->assertOk();
        $this->assertSame(1, $response->json('deleted'));
        $this->assertSame(
            1,
            ActivityLog::query()->where('subject_label', 'KUNCI INGGRIS')->count()
        );
    }

    public function test_login_dan_logout_tidak_pernah_terhapus(): void
    {
        ['user' => $user, 'area' => $area] = $this->siapkanSuperAdmin();

        $this->buatLog($area, ['action' => 'login']);
        $this->buatLog($area, ['action' => 'logout']);
        $this->buatLog($area, ['action' => 'create']);

        Sanctum::actingAs($user);

        $response = $this->deleteJson('/api/activity-logs', ['password' => self::SANDI]);

        $response->assertOk();
        $this->assertSame(1, $response->json('deleted'));
        $this->assertSame(1, ActivityLog::query()->where('action', 'login')->count());
        $this->assertSame(1, ActivityLog::query()->where('action', 'logout')->count());
    }

    public function test_tanpa_filter_menghapus_seluruh_entri_yang_terlihat(): void
    {
        ['user' => $user, 'area' => $area] = $this->siapkanSuperAdmin();

        // Lebih dari satu batch (500) untuk memastikan penghapusan bertahap
        // menghabiskan seluruh entri, bukan hanya batch pertama.
        $baris = [];
        for ($i = 0; $i < 620; $i += 1) {
            $baris[] = [
                'area_id' => $area->id,
                'actor_name' => 'Penguji',
                'action' => 'create',
                'subject_type' => 'Alat',
                'subject_label' => 'Alat '.$i,
                'description' => 'Membuat alat '.$i,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        ActivityLog::query()->insert($baris);

        Sanctum::actingAs($user);

        $response = $this->deleteJson('/api/activity-logs', ['password' => self::SANDI]);

        $response->assertOk();
        $this->assertSame(620, $response->json('deleted'));
        $this->assertSame(0, ActivityLog::query()->where('action', 'create')->count());
    }

    public function test_penghapusan_dicatat_sebagai_entri_baru(): void
    {
        ['user' => $user, 'area' => $area] = $this->siapkanSuperAdmin();

        $this->buatLog($area, ['action' => 'create']);

        Sanctum::actingAs($user);

        $this->deleteJson('/api/activity-logs?action=create', ['password' => self::SANDI])
            ->assertOk();

        $catatan = ActivityLog::query()->where('subject_type', 'ActivityLog')->first();

        $this->assertNotNull($catatan);
        $this->assertSame('delete', $catatan->action);
        $this->assertSame($user->id, $catatan->user_id);
        $this->assertSame(1, $catatan->properties['deleted_count']);
        $this->assertStringContainsString('aksi create', $catatan->description);
    }

    public function test_kata_sandi_salah_ditolak_dan_tidak_menghapus(): void
    {
        ['user' => $user, 'area' => $area] = $this->siapkanSuperAdmin();

        $this->buatLog($area, ['action' => 'create']);

        Sanctum::actingAs($user);

        $this->deleteJson('/api/activity-logs', ['password' => 'sandi-yang-salah'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('password');

        $this->assertSame(1, ActivityLog::query()->count());
    }

    public function test_kata_sandi_kosong_ditolak_dan_tidak_menghapus(): void
    {
        ['user' => $user, 'area' => $area] = $this->siapkanSuperAdmin();

        $this->buatLog($area, ['action' => 'create']);

        Sanctum::actingAs($user);

        $this->deleteJson('/api/activity-logs', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors('password');

        $this->assertSame(1, ActivityLog::query()->count());
    }

    public function test_kata_sandi_tidak_pernah_dicatat_di_log(): void
    {
        ['user' => $user, 'area' => $area] = $this->siapkanSuperAdmin();

        $this->buatLog($area, ['action' => 'create']);

        Sanctum::actingAs($user);

        $this->deleteJson('/api/activity-logs?action=create', ['password' => self::SANDI])
            ->assertOk();

        $catatan = ActivityLog::query()->where('subject_type', 'ActivityLog')->firstOrFail();

        $jejak = json_encode([
            $catatan->description,
            $catatan->properties,
            $catatan->old_values,
            $catatan->new_values,
            $catatan->url,
        ]);

        $this->assertStringNotContainsString(self::SANDI, (string) $jejak);
    }

    public function test_selain_super_admin_tidak_boleh_menghapus(): void
    {
        $area = Area::create(['name' => 'Area 1', 'slug' => 'A1', 'kode' => 'A1']);
        $role = Role::create(['key' => Role::KEY_ADMIN, 'name' => 'Admin']);
        $user = User::factory()->create([
            'role_id' => $role->id,
            'area_id' => $area->id,
            'password' => Hash::make(self::SANDI),
        ]);

        $this->buatLog($area, ['action' => 'create']);

        Sanctum::actingAs($user);

        $this->deleteJson('/api/activity-logs', ['password' => self::SANDI])
            ->assertStatus(403);

        $this->assertSame(1, ActivityLog::query()->count());
    }

    private function siapkanSuperAdmin(): array
    {
        $area = Area::create(['name' => 'Area 1', 'slug' => 'A1', 'kode' => 'A1']);
        $role = Role::create(['key' => Role::KEY_SUPER_ADMIN, 'name' => 'Super Admin']);
        $user = User::factory()->create([
            'role_id' => $role->id,
            'area_id' => $area->id,
            'password' => Hash::make(self::SANDI),
        ]);

        return ['user' => $user, 'area' => $area];
    }

    private function buatLog(Area $area, array $atribut = []): ActivityLog
    {
        return ActivityLog::create(array_merge([
            'area_id' => $area->id,
            'actor_name' => 'Penguji',
            'action' => 'create',
            'subject_type' => 'Alat',
            'subject_label' => 'Alat Uji',
            'description' => 'Deskripsi uji',
        ], $atribut));
    }
}
