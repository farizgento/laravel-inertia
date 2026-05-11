<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AlatImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_pic_tools_can_import_tools_from_csv_using_area_slug(): void
    {
        $role = Role::create([
            'key' => Role::KEY_PIC_TOOLS,
            'name' => 'PIC Tools',
        ]);

        $area = Area::create([
            'name' => 'UPHK',
            'slug' => 'uphk',
            'kode' => 'UPHK',
        ]);

        $user = User::factory()->create([
            'role_id' => $role->id,
            'area_id' => $area->id,
        ]);

        Sanctum::actingAs($user);

        $csvContent = implode("\n", [
            'nama alat,jenis alat,klasifikasi alat,total aset,area',
            'Megger,Alat Ukur,Elektrikal,12,uphk',
        ]);

        $file = UploadedFile::fake()->createWithContent('alat.csv', $csvContent);

        $response = $this->postJson('/api/alats/import', [
            'file' => $file,
        ]);

        $response
            ->assertOk()
            ->assertJsonFragment([
                'created' => 1,
                'updated' => 0,
            ]);

        $this->assertDatabaseHas('alats', [
            'nama' => 'Megger',
            'jenis_alat' => 'Alat Ukur',
            'klasifikasi_alat' => 'Elektrikal',
            'total_aset' => 12,
            'area_id' => $area->id,
        ]);
    }

    public function test_non_super_admin_cannot_create_tool_in_another_area(): void
    {
        $role = Role::create([
            'key' => Role::KEY_PIC_TOOLS,
            'name' => 'PIC Tools',
        ]);

        $ownArea = Area::create([
            'name' => 'Area 1.1',
            'slug' => 'I.1',
            'kode' => 'Area1.1',
        ]);

        $otherArea = Area::create([
            'name' => 'UPHK',
            'slug' => 'uphk',
            'kode' => 'UPHK',
        ]);

        $user = User::factory()->create([
            'role_id' => $role->id,
            'area_id' => $ownArea->id,
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/alats', [
            'nama' => 'Clamp Meter',
            'jenis_alat' => 'Alat Ukur',
            'klasifikasi_alat' => 'Elektrikal',
            'total_aset' => 5,
            'area_id' => $otherArea->id,
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['area_id']);

        $this->assertDatabaseMissing('alats', [
            'nama' => 'Clamp Meter',
            'area_id' => $otherArea->id,
        ]);
    }
}
