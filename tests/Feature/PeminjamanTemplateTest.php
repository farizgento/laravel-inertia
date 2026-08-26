<?php

namespace Tests\Feature;

use App\Models\Alat;
use App\Models\Area;
use App\Models\Peminjaman;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PeminjamanTemplateTest extends TestCase
{
    use RefreshDatabase;

    public function test_pic_tool_can_create_intra_area_template_for_own_area(): void
    {
        [$role, $area] = $this->createRoleAndArea(Role::KEY_PIC_TOOL);
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

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/peminjaman-templates', [
            'nama' => 'Template Overhaul',
            'kategori' => Peminjaman::KATEGORI_INTRA_AREA,
            'area_id' => $area->id,
            'items' => [
                ['id' => $alat->id, 'qty' => 4],
            ],
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('nama', 'Template Overhaul')
            ->assertJsonPath('items.0.qty', 4);

        $this->assertDatabaseHas('peminjaman_templates', [
            'nama' => 'Template Overhaul',
            'area_id' => $area->id,
            'kategori' => Peminjaman::KATEGORI_INTRA_AREA,
        ]);
        $this->assertDatabaseHas('peminjaman_template_items', [
            'alat_id' => $alat->id,
            'qty' => 4,
        ]);
    }

    public function test_pic_tool_cannot_create_template_for_other_area(): void
    {
        [$role, $ownArea] = $this->createRoleAndArea(Role::KEY_PIC_TOOL);
        $otherArea = Area::create([
            'name' => 'UPHK',
            'slug' => 'uphk',
            'kode' => 'UPHK',
        ]);
        $user = User::factory()->create([
            'role_id' => $role->id,
            'area_id' => $ownArea->id,
        ]);
        $alat = Alat::create([
            'nama' => 'Clamp Meter',
            'jenis_alat' => 'Alat Ukur',
            'klasifikasi_alat' => 'Elektrikal',
            'total_aset' => 5,
            'area_id' => $otherArea->id,
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/peminjaman-templates', [
            'nama' => 'Template Area Lain',
            'kategori' => Peminjaman::KATEGORI_INTRA_AREA,
            'area_id' => $otherArea->id,
            'items' => [
                ['id' => $alat->id, 'qty' => 1],
            ],
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['area_id']);
    }

    public function test_user_can_read_templates_for_own_area(): void
    {
        [$picRole, $area] = $this->createRoleAndArea(Role::KEY_PIC_TOOL);
        $userRole = Role::create([
            'key' => Role::KEY_USER,
            'name' => 'User',
        ]);
        $pic = User::factory()->create([
            'role_id' => $picRole->id,
            'area_id' => $area->id,
        ]);
        $user = User::factory()->create([
            'role_id' => $userRole->id,
            'area_id' => $area->id,
        ]);
        $alat = Alat::create([
            'nama' => 'Multimeter',
            'jenis_alat' => 'Alat Ukur',
            'klasifikasi_alat' => 'Elektrikal',
            'total_aset' => 8,
            'area_id' => $area->id,
        ]);

        Sanctum::actingAs($pic);
        $this->postJson('/api/peminjaman-templates', [
            'nama' => 'Template Preventive',
            'kategori' => Peminjaman::KATEGORI_INTRA_AREA,
            'area_id' => $area->id,
            'items' => [
                ['id' => $alat->id, 'qty' => 2],
            ],
        ])->assertCreated();

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/peminjaman-templates?kategori=Intra%20Area');

        $response
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.nama', 'Template Preventive')
            ->assertJsonPath('0.items.0.alat_id', $alat->id);
    }

    public function test_availability_trims_template_quantity_to_current_stock(): void
    {
        [$role, $area] = $this->createRoleAndArea(Role::KEY_USER);
        $user = User::factory()->create([
            'role_id' => $role->id,
            'area_id' => $area->id,
        ]);
        $alat = Alat::create([
            'nama' => 'Power Analyzer',
            'jenis_alat' => 'Alat Ukur',
            'klasifikasi_alat' => 'Elektrikal',
            'total_aset' => 10,
            'area_id' => $area->id,
        ]);
        $peminjaman = Peminjaman::create([
            'user_id' => $user->id,
            'area_id' => $area->id,
            'status' => Peminjaman::STATUS_DISETUJUI,
            'kategori' => Peminjaman::KATEGORI_INTRA_AREA,
            'tanggal_pinjam' => now()->toDateString(),
            'tanggal_kembali' => now()->addDay()->toDateString(),
            'pekerjaan' => 'Existing Job',
        ]);
        $peminjaman->items()->create([
            'alat_id' => $alat->id,
            'qty' => 4,
            'approved_qty' => 4,
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/alats/availability', [
            'area_id' => $area->id,
            'items' => [
                ['id' => $alat->id, 'qty' => 10],
            ],
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('data.0.requested_qty', 10)
            ->assertJsonPath('data.0.available_qty', 6)
            ->assertJsonPath('data.0.usable_qty', 6)
            ->assertJsonPath('data.0.total_aset', 10);
    }

    private function createRoleAndArea(string $roleKey): array
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

        return [$role, $area];
    }
}
