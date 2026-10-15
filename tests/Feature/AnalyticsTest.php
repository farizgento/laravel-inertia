<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Area;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AnalyticsTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_melihat_keaktifan_semua_area(): void
    {
        $trla = $this->buatArea('TOOLS & RLA', 'TRLA');
        $areaLain = $this->buatArea('Area 1.1', 'Area1.1');

        $superAdmin = $this->buatUser(Role::KEY_SUPER_ADMIN, $trla);
        $picTrla = $this->buatUser(Role::KEY_PIC_TOOL, $trla);
        $picLain = $this->buatUser(Role::KEY_PIC_TOOL, $areaLain);
        // Pengguna ini tidak pernah beraktivitas, jadi tidak dihitung sebagai aktif.
        $this->buatUser(Role::KEY_USER, $areaLain);

        $this->buatLog($picTrla, 'login');
        $this->buatLog($picTrla, 'create');
        $this->buatLog($picTrla, 'update');
        $this->buatLog($picLain, 'login');

        Sanctum::actingAs($superAdmin);

        $response = $this->getJson('/api/analytics?hari=30')->assertOk();

        $response
            ->assertJsonPath('ringkasan.total_aktivitas', 4)
            ->assertJsonPath('ringkasan.total_login', 2)
            ->assertJsonPath('ringkasan.pengguna_aktif', 2)
            ->assertJsonPath('ringkasan.total_pengguna', 4)
            ->assertJsonPath('ringkasan.area_aktif', 2)
            ->assertJsonPath('ringkasan.total_area', 2)
            // Diurutkan dari area paling aktif.
            ->assertJsonPath('area.0.kode', 'TRLA')
            ->assertJsonPath('area.0.aktivitas', 3)
            ->assertJsonPath('area.0.login', 1)
            ->assertJsonPath('area.1.aktivitas', 1)
            ->assertJsonPath('rincian_aksi.login', 2);
    }

    public function test_aktivitas_di_luar_periode_tidak_dihitung(): void
    {
        $trla = $this->buatArea('TOOLS & RLA', 'TRLA');
        $admin = $this->buatUser(Role::KEY_ADMIN, $trla);

        $this->buatLog($admin, 'create');
        $this->buatLog($admin, 'create', now()->subDays(45));

        Sanctum::actingAs($admin);

        $this->getJson('/api/analytics?hari=30')->assertOk()->assertJsonPath('ringkasan.total_aktivitas', 1);
        $this->getJson('/api/analytics?hari=90')->assertOk()->assertJsonPath('ringkasan.total_aktivitas', 2);
    }

    public function test_sp_tool_area_lain_tidak_boleh_membuka_analytics(): void
    {
        $this->buatArea('TOOLS & RLA', 'TRLA');
        $areaLain = $this->buatArea('Area 1.1', 'Area1.1');

        Sanctum::actingAs($this->buatUser(Role::KEY_SP_TOOL, $areaLain));

        $this->getJson('/api/analytics')->assertForbidden();
    }

    public function test_sp_tool_trla_boleh_membuka_analytics(): void
    {
        $trla = $this->buatArea('TOOLS & RLA', 'TRLA');

        Sanctum::actingAs($this->buatUser(Role::KEY_SP_TOOL, $trla));

        $this->getJson('/api/analytics')->assertOk();
    }

    public function test_pic_tool_tidak_boleh_membuka_analytics(): void
    {
        $trla = $this->buatArea('TOOLS & RLA', 'TRLA');

        Sanctum::actingAs($this->buatUser(Role::KEY_PIC_TOOL, $trla));

        $this->getJson('/api/analytics')->assertForbidden();
    }

    private function buatArea(string $nama, string $kode): Area
    {
        return Area::create([
            'name' => $nama,
            'slug' => strtolower(str_replace([' ', '&', '.'], '-', $nama)),
            'kode' => $kode,
        ]);
    }

    private function buatUser(string $roleKey, Area $area): User
    {
        $role = Role::firstOrCreate(['key' => $roleKey], ['name' => $roleKey]);

        return User::factory()->create([
            'role_id' => $role->id,
            'area_id' => $area->id,
        ]);
    }

    private function buatLog(User $user, string $action, $waktu = null): ActivityLog
    {
        $log = ActivityLog::create([
            'user_id' => $user->id,
            'area_id' => $user->area_id,
            'actor_name' => $user->name,
            'actor_role_key' => $user->role?->key,
            'actor_area_id' => $user->area_id,
            'action' => $action,
            'subject_type' => $action === 'login' ? 'Auth' : 'Alat',
        ]);

        if ($waktu) {
            // created_at tidak mass assignable, jadi ditulis langsung.
            $log->forceFill(['created_at' => $waktu, 'updated_at' => $waktu])->save();
        }

        return $log;
    }
}
