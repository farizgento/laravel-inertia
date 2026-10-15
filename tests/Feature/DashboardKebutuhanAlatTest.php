<?php

namespace Tests\Feature;

use App\Models\Alat;
use App\Models\Area;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DashboardKebutuhanAlatTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_merangkum_alat_kekurangan_dan_kelebihan(): void
    {
        ['user' => $user, 'area' => $area] = $this->siapkan();

        $this->buatAlat($area, 'Kunci Pas 10', totalAset: 2, maxDemand: 6);   // kurang 4
        $this->buatAlat($area, 'Kunci Pas 12', totalAset: 1, maxDemand: 10);  // kurang 9
        $this->buatAlat($area, 'Megger', totalAset: 12, maxDemand: 4);        // lebih 8
        $this->buatAlat($area, 'Torque Wrench', totalAset: 5, maxDemand: 5);  // sesuai
        $this->buatAlat($area, 'Belum Diisi', totalAset: 7, maxDemand: 0);    // belum ditentukan

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/dashboard')->assertOk();

        $response
            ->assertJsonPath('kebutuhan.ringkasan.jenis_alat', 5)
            ->assertJsonPath('kebutuhan.ringkasan.kekurangan', 2)
            ->assertJsonPath('kebutuhan.ringkasan.kelebihan', 1)
            ->assertJsonPath('kebutuhan.ringkasan.sesuai', 1)
            // Alat dengan max demand 0 tidak dihitung sebagai kelebihan.
            ->assertJsonPath('kebutuhan.ringkasan.belum_ditentukan', 1)
            ->assertJsonPath('kebutuhan.ringkasan.unit_kekurangan', 13)
            ->assertJsonPath('kebutuhan.ringkasan.unit_kelebihan', 8);

        // Daftar diurutkan dari selisih terbesar.
        $response
            ->assertJsonPath('kebutuhan.paling_kurang.0.nama', 'Kunci Pas 12')
            ->assertJsonPath('kebutuhan.paling_kurang.0.selisih', 9)
            ->assertJsonPath('kebutuhan.paling_kurang.1.nama', 'Kunci Pas 10')
            ->assertJsonCount(2, 'kebutuhan.paling_kurang')
            ->assertJsonPath('kebutuhan.paling_lebih.0.nama', 'Megger')
            ->assertJsonPath('kebutuhan.paling_lebih.0.selisih', 8)
            ->assertJsonCount(1, 'kebutuhan.paling_lebih');
    }

    public function test_alat_area_lain_tidak_ikut_dihitung(): void
    {
        ['user' => $user, 'area' => $area] = $this->siapkan();
        $areaLain = Area::create(['name' => 'Area 2', 'slug' => 'area-2', 'kode' => 'A2']);

        $this->buatAlat($area, 'Kunci Pas 10', totalAset: 2, maxDemand: 6);
        $this->buatAlat($areaLain, 'Kunci Pas Area Lain', totalAset: 1, maxDemand: 20);

        Sanctum::actingAs($user);

        $this->getJson('/api/dashboard')
            ->assertOk()
            ->assertJsonPath('kebutuhan.ringkasan.jenis_alat', 1)
            ->assertJsonPath('kebutuhan.ringkasan.unit_kekurangan', 4);
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
