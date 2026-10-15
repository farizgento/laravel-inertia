<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\PekerjaanOh;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MasterOverhaulTest extends TestCase
{
    use RefreshDatabase;

    public function test_pic_tool_can_create_pekerjaan_oh(): void
    {
        $user = $this->buatUser(Role::KEY_PIC_TOOL);

        Sanctum::actingAs($user);

        $this->postJson('/api/pekerjaan-ohs', [
            'jenis' => 'STANDAR',
            'nama' => 'ME PLTU 1 UBP ADIPALA',
            'tanggal_mulai' => '2026-12-25',
            'tanggal_selesai' => '2027-02-04',
            'siteco' => 'Agung Prayoga',
        ])
            ->assertCreated()
            ->assertJsonPath('data.jenis', 'STANDAR')
            // Lama OH dihitung inklusif: 25 Des 2026 - 4 Feb 2027 = 42 hari.
            ->assertJsonPath('data.lama_oh', 42)
            ->assertJsonPath('data.tanggal_mulai', '25 December 2026')
            ->assertJsonPath('data.created_by_nama', $user->name);

        $this->assertDatabaseHas('pekerjaan_ohs', [
            'nama' => 'ME PLTU 1 UBP ADIPALA',
            'jenis' => 'STANDAR',
            'siteco' => 'Agung Prayoga',
            'created_by' => $user->id,
        ]);
    }

    public function test_pekerjaan_oh_without_end_date_has_no_duration(): void
    {
        Sanctum::actingAs($this->buatUser(Role::KEY_PIC_TOOL));

        $this->postJson('/api/pekerjaan-ohs', [
            'nama' => 'Overhaul Tanpa Tanggal Selesai',
            'tanggal_mulai' => '2026-12-25',
        ])
            ->assertCreated()
            ->assertJsonPath('data.lama_oh', null)
            ->assertJsonPath('data.tanggal_selesai', null);
    }

    public function test_pekerjaan_oh_rejects_end_date_before_start_date(): void
    {
        Sanctum::actingAs($this->buatUser(Role::KEY_PIC_TOOL));

        $this->postJson('/api/pekerjaan-ohs', [
            'nama' => 'Overhaul Salah Jadwal',
            'tanggal_mulai' => '2026-11-10',
            'tanggal_selesai' => '2026-11-01',
        ])->assertStatus(422)->assertJsonValidationErrors('tanggal_selesai');
    }

    public function test_pekerjaan_oh_can_be_searched_and_filtered_by_jenis(): void
    {
        $user = $this->buatUser(Role::KEY_ADMIN);
        PekerjaanOh::create([
            'jenis' => 'STANDAR',
            'nama' => 'ME PLTU 1 UBP ADIPALA',
            'tanggal_mulai' => '2026-12-25',
            'tanggal_selesai' => '2027-02-04',
            'created_by' => $user->id,
        ]);
        PekerjaanOh::create([
            'jenis' => 'MINOR',
            'nama' => 'MINOR PLTGU T. Lorok ST 1.0 UBP SEMARANG',
            'tanggal_mulai' => '2027-08-02',
            'tanggal_selesai' => '2027-08-31',
            'created_by' => $user->id,
        ]);

        Sanctum::actingAs($user);

        $this->getJson('/api/pekerjaan-ohs')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            // Diurutkan berdasarkan tanggal mulai terdekat.
            ->assertJsonPath('data.0.nama', 'ME PLTU 1 UBP ADIPALA')
            ->assertJsonPath('jenis_tersedia', ['MINOR', 'STANDAR']);

        $this->getJson('/api/pekerjaan-ohs?jenis=MINOR')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.jenis', 'MINOR');

        $this->getJson('/api/pekerjaan-ohs?search=semarang')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.jenis', 'MINOR');
    }

    public function test_ringkasan_mengelompokkan_pekerjaan_per_tahap_jadwal(): void
    {
        $user = $this->buatUser(Role::KEY_PIC_TOOL);

        // Akan datang.
        PekerjaanOh::create([
            'nama' => 'Overhaul Bulan Depan',
            'tanggal_mulai' => now()->addDays(20)->toDateString(),
            'tanggal_selesai' => now()->addDays(30)->toDateString(),
            'created_by' => $user->id,
        ]);
        // Sedang berlangsung.
        PekerjaanOh::create([
            'nama' => 'Overhaul Berjalan',
            'tanggal_mulai' => now()->subDays(3)->toDateString(),
            'tanggal_selesai' => now()->addDays(4)->toDateString(),
            'created_by' => $user->id,
        ]);
        // Sudah lewat.
        PekerjaanOh::create([
            'nama' => 'Overhaul Lampau',
            'tanggal_mulai' => now()->subDays(30)->toDateString(),
            'tanggal_selesai' => now()->subDays(10)->toDateString(),
            'created_by' => $user->id,
        ]);

        Sanctum::actingAs($user);

        $this->getJson('/api/pekerjaan-ohs')
            ->assertOk()
            ->assertJsonPath('ringkasan.total', 3)
            ->assertJsonPath('ringkasan.akan_datang', 1)
            ->assertJsonPath('ringkasan.berlangsung', 1)
            ->assertJsonPath('ringkasan.selesai', 1);

        // Ringkasan mengikuti filter yang sedang dipakai.
        $this->getJson('/api/pekerjaan-ohs?search=berjalan')
            ->assertOk()
            ->assertJsonPath('ringkasan.total', 1)
            ->assertJsonPath('ringkasan.berlangsung', 1);
    }

    public function test_pekerjaan_oh_can_be_updated_and_deleted(): void
    {
        $user = $this->buatUser(Role::KEY_PIC_TOOL);
        $pekerjaan = PekerjaanOh::create([
            'jenis' => 'STANDAR',
            'nama' => 'Overhaul Awal',
            'tanggal_mulai' => '2026-12-25',
            'created_by' => $user->id,
        ]);

        Sanctum::actingAs($user);

        $this->putJson("/api/pekerjaan-ohs/{$pekerjaan->id}", [
            'jenis' => 'MINOR',
            'nama' => 'Overhaul Diperbarui',
            'tanggal_mulai' => '2026-12-25',
            'tanggal_selesai' => '2026-12-31',
        ])
            ->assertOk()
            ->assertJsonPath('data.nama', 'Overhaul Diperbarui')
            ->assertJsonPath('data.lama_oh', 7);

        $this->deleteJson("/api/pekerjaan-ohs/{$pekerjaan->id}")->assertOk();
        $this->assertDatabaseCount('pekerjaan_ohs', 0);
    }

    public function test_pilihan_site_coordinator_hanya_pengguna_area_sendiri(): void
    {
        $role = Role::create(['key' => Role::KEY_PIC_TOOL, 'name' => 'PIC Tool']);
        $area = Area::create(['name' => 'Area 1', 'slug' => 'area-1', 'kode' => 'A1']);
        $areaLain = Area::create(['name' => 'Area 2', 'slug' => 'area-2', 'kode' => 'A2']);

        $pic = User::factory()->create(['role_id' => $role->id, 'area_id' => $area->id, 'name' => 'Budi']);
        User::factory()->create(['role_id' => $role->id, 'area_id' => $area->id, 'name' => 'Agung']);
        User::factory()->create(['role_id' => $role->id, 'area_id' => $areaLain->id, 'name' => 'Candra']);

        Sanctum::actingAs($pic);

        $hasil = $this->getJson('/api/pekerjaan-ohs/pengguna-area')->assertOk()->json();

        // Diurutkan berdasarkan nama dan hanya berisi pengguna area sendiri.
        $this->assertSame(['Agung', 'Budi'], array_column($hasil, 'nama'));
    }

    public function test_user_role_cannot_access_pekerjaan_oh(): void
    {
        Sanctum::actingAs($this->buatUser(Role::KEY_USER));

        $this->getJson('/api/pekerjaan-ohs')->assertForbidden();
    }

    private function buatUser(string $roleKey): User
    {
        $role = Role::create(['key' => $roleKey, 'name' => $roleKey]);
        $area = Area::create(['name' => 'Area 1', 'slug' => 'area-1', 'kode' => 'A1']);

        return User::factory()->create([
            'role_id' => $role->id,
            'area_id' => $area->id,
        ]);
    }
}
