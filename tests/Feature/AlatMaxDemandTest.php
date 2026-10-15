<?php

namespace Tests\Feature;

use App\Models\Alat;
use App\Models\Area;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AlatMaxDemandTest extends TestCase
{
    use RefreshDatabase;

    public function test_status_kebutuhan_dihitung_dari_total_aset(): void
    {
        ['user' => $user, 'area' => $area] = $this->siapkan();

        $this->buatAlat($area, 'Kunci Ring Pas 10', totalAset: 10, maxDemand: 4);
        $this->buatAlat($area, 'Kunci Ring Pas 12', totalAset: 2, maxDemand: 6);
        $this->buatAlat($area, 'Kunci Ring Pas 14', totalAset: 5, maxDemand: 5);

        Sanctum::actingAs($user);

        $rows = collect($this->getJson('/api/alats')->assertOk()->json())->keyBy('nama');

        $this->assertSame('Kelebihan', $rows['Kunci Ring Pas 10']['status_demand']);
        $this->assertSame(6, $rows['Kunci Ring Pas 10']['selisih_demand']);

        $this->assertSame('Kekurangan', $rows['Kunci Ring Pas 12']['status_demand']);
        $this->assertSame(-4, $rows['Kunci Ring Pas 12']['selisih_demand']);

        $this->assertSame('Sesuai', $rows['Kunci Ring Pas 14']['status_demand']);
        $this->assertSame(0, $rows['Kunci Ring Pas 14']['selisih_demand']);
    }

    public function test_daftar_alat_bisa_disaring_per_status_kebutuhan(): void
    {
        ['user' => $user, 'area' => $area] = $this->siapkan();

        $this->buatAlat($area, 'Alat Kelebihan', totalAset: 10, maxDemand: 4);
        $this->buatAlat($area, 'Alat Kekurangan', totalAset: 2, maxDemand: 6);
        $this->buatAlat($area, 'Alat Sesuai', totalAset: 5, maxDemand: 5);

        Sanctum::actingAs($user);

        foreach ([
            'kekurangan' => 'Alat Kekurangan',
            'kelebihan' => 'Alat Kelebihan',
            'sesuai' => 'Alat Sesuai',
        ] as $status => $nama) {
            $hasil = $this->getJson("/api/alats?status_demand={$status}")->assertOk()->json();

            $this->assertCount(1, $hasil, "Filter {$status} harus mengembalikan satu alat.");
            $this->assertSame($nama, $hasil[0]['nama']);
        }
    }

    public function test_max_demand_bisa_disimpan_lewat_form(): void
    {
        ['user' => $user, 'area' => $area] = $this->siapkan();

        Sanctum::actingAs($user);

        $this->postJson('/api/alats', [
            'nama' => 'Ring - pas 11',
            'jenis_alat' => 'Kunci Ring Pas',
            'klasifikasi_alat' => 'General Tools',
            'total_aset' => 2,
            'max_demand' => 4,
            'area_id' => $area->id,
        ])
            ->assertCreated()
            ->assertJsonPath('max_demand', 4)
            ->assertJsonPath('status_demand', 'Kekurangan');

        $alat = Alat::query()->firstOrFail();
        $this->assertSame(4, (int) $alat->max_demand);

        $this->putJson("/api/alats/{$alat->id}", [
            'nama' => 'Ring - pas 11',
            'jenis_alat' => 'Kunci Ring Pas',
            'klasifikasi_alat' => 'General Tools',
            'total_aset' => 6,
            'max_demand' => 4,
            'area_id' => $area->id,
        ])
            ->assertOk()
            ->assertJsonPath('status_demand', 'Kelebihan')
            ->assertJsonPath('selisih_demand', 2);
    }

    public function test_max_demand_default_nol_bila_tidak_diisi(): void
    {
        ['user' => $user, 'area' => $area] = $this->siapkan();

        Sanctum::actingAs($user);

        $this->postJson('/api/alats', [
            'nama' => 'Tanpa Kebutuhan',
            'jenis_alat' => 'Kunci Ring Pas',
            'klasifikasi_alat' => 'General Tools',
            'total_aset' => 3,
            'area_id' => $area->id,
        ])
            ->assertCreated()
            ->assertJsonPath('max_demand', 0)
            ->assertJsonPath('status_demand', 'Kelebihan');
    }

    /**
     * @return array{user: User, area: Area}
     */
    private function siapkan(): array
    {
        $role = Role::create(['key' => Role::KEY_PIC_TOOL, 'name' => 'PIC Tool']);
        $area = Area::create(['name' => 'Area II.2', 'slug' => 'area-ii-2', 'kode' => 'II2']);
        $user = User::factory()->create([
            'role_id' => $role->id,
            'area_id' => $area->id,
        ]);

        return ['user' => $user, 'area' => $area];
    }

    private function buatAlat(Area $area, string $nama, int $totalAset, int $maxDemand): Alat
    {
        return Alat::create([
            'nama' => $nama,
            'jenis_alat' => 'Kunci Ring Pas',
            'klasifikasi_alat' => 'General Tools',
            'total_aset' => $totalAset,
            'max_demand' => $maxDemand,
            'area_id' => $area->id,
        ]);
    }
}
