<?php

namespace Tests\Feature;

use App\Models\Alat;
use App\Models\Area;
use App\Models\LaporanAlat;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class LaporanAlatStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_sp_tool_can_approve_pending_report_and_stock_is_reduced(): void
    {
        ['user' => $user, 'laporan' => $laporan, 'alat' => $alat] = $this->buatLaporan(
            Role::KEY_SP_TOOL,
            LaporanAlat::STATUS_DILAPORKAN
        );

        Sanctum::actingAs($user);

        $this->putJson("/api/laporan-kerusakan/{$laporan->id}", [
            'status' => LaporanAlat::STATUS_DISETUJUI,
        ])->assertOk()->assertJsonPath('status', LaporanAlat::STATUS_DISETUJUI);

        $this->assertSame(7, (int) $alat->fresh()->total_aset);
    }

    public function test_selesai_is_no_longer_a_valid_status(): void
    {
        ['user' => $user, 'laporan' => $laporan] = $this->buatLaporan(
            Role::KEY_PIC_TOOL,
            LaporanAlat::STATUS_DILAPORKAN
        );

        Sanctum::actingAs($user);

        $this->putJson("/api/laporan-kerusakan/{$laporan->id}", [
            'status' => 'Selesai',
        ])->assertStatus(422)->assertJsonValidationErrors('status');
    }

    public function test_sp_tool_cannot_change_final_report(): void
    {
        ['user' => $user, 'laporan' => $laporan, 'alat' => $alat] = $this->buatLaporan(
            Role::KEY_SP_TOOL,
            LaporanAlat::STATUS_DISETUJUI
        );

        Sanctum::actingAs($user);

        $this->putJson("/api/laporan-kerusakan/{$laporan->id}", [
            'status' => LaporanAlat::STATUS_DITOLAK,
        ])->assertForbidden();

        $this->assertSame(LaporanAlat::STATUS_DISETUJUI, $laporan->fresh()->status);
        $this->assertSame(10, (int) $alat->fresh()->total_aset);
    }

    public function test_mgr_tool_cannot_change_final_report(): void
    {
        ['user' => $user, 'laporan' => $laporan] = $this->buatLaporan(
            Role::KEY_MGR_TOOL,
            LaporanAlat::STATUS_DISETUJUI
        );

        Sanctum::actingAs($user);

        $this->putJson("/api/laporan-kerusakan/{$laporan->id}", [
            'status' => LaporanAlat::STATUS_DITOLAK,
        ])->assertForbidden();
    }

    /**
     * @dataProvider roleKoreksiProvider
     */
    public function test_role_koreksi_can_fix_wrongly_approved_report(string $roleKey): void
    {
        ['user' => $user, 'laporan' => $laporan, 'alat' => $alat] = $this->buatLaporan(
            $roleKey,
            LaporanAlat::STATUS_DISETUJUI
        );

        Sanctum::actingAs($user);

        $this->putJson("/api/laporan-kerusakan/{$laporan->id}", [
            'status' => LaporanAlat::STATUS_DITOLAK,
        ])->assertOk()->assertJsonPath('status', LaporanAlat::STATUS_DITOLAK);

        // Stok yang tadi dipotong saat disetujui harus kembali.
        $this->assertSame(13, (int) $alat->fresh()->total_aset);
    }

    /**
     * @dataProvider roleKoreksiProvider
     */
    public function test_role_koreksi_can_return_report_to_pending(string $roleKey): void
    {
        ['user' => $user, 'laporan' => $laporan, 'alat' => $alat] = $this->buatLaporan(
            $roleKey,
            LaporanAlat::STATUS_DISETUJUI
        );

        Sanctum::actingAs($user);

        $this->putJson("/api/laporan-kerusakan/{$laporan->id}", [
            'status' => LaporanAlat::STATUS_DILAPORKAN,
        ])->assertOk()->assertJsonPath('status', LaporanAlat::STATUS_DILAPORKAN);

        $this->assertSame(13, (int) $alat->fresh()->total_aset);
    }

    public function test_pic_tool_can_fix_legacy_selesai_report(): void
    {
        ['user' => $user, 'laporan' => $laporan] = $this->buatLaporan(
            Role::KEY_PIC_TOOL,
            'Selesai'
        );

        Sanctum::actingAs($user);

        $this->putJson("/api/laporan-kerusakan/{$laporan->id}", [
            'status' => LaporanAlat::STATUS_DISETUJUI,
        ])->assertOk()->assertJsonPath('status', LaporanAlat::STATUS_DISETUJUI);
    }

    public static function roleKoreksiProvider(): array
    {
        return [
            'pic tool' => [Role::KEY_PIC_TOOL],
            'admin' => [Role::KEY_ADMIN],
            'super admin' => [Role::KEY_SUPER_ADMIN],
        ];
    }

    /**
     * @return array{user: User, laporan: LaporanAlat, alat: Alat}
     */
    private function buatLaporan(string $roleKey, string $status): array
    {
        $role = Role::create([
            'key' => $roleKey,
            'name' => $roleKey,
        ]);
        $area = Area::create([
            'name' => 'Area 1',
            'slug' => 'area-1',
            'kode' => 'A1',
        ]);
        $user = User::factory()->create([
            'role_id' => $role->id,
            'area_id' => $area->id,
        ]);
        $alat = Alat::create([
            'nama' => 'Megger',
            'jenis_alat' => 'Alat Ukur',
            'klasifikasi_alat' => 'Elektrikal',
            'total_aset' => 10,
            'area_id' => $area->id,
        ]);
        $laporan = LaporanAlat::create([
            'kategori' => LaporanAlat::CATEGORY_KERUSAKAN,
            'deskripsi' => 'Kabel putus',
            'status' => $status,
            'jumlah' => 3,
            'alat_id' => $alat->id,
            'user_id' => $user->id,
            'area_id' => $area->id,
            'path' => 'kerusakan/1/foto.jpg',
            'original_name' => 'foto.jpg',
            'mime' => 'image/jpeg',
            'size' => 1024,
        ]);

        return ['user' => $user, 'laporan' => $laporan, 'alat' => $alat];
    }
}
