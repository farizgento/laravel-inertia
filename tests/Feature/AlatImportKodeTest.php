<?php

namespace Tests\Feature;

use App\Models\Alat;
use App\Models\Area;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AlatImportKodeTest extends TestCase
{
    use RefreshDatabase;

    public function test_nama_sama_dengan_kode_berbeda_jadi_dua_alat(): void
    {
        ['user' => $user, 'area' => $area] = $this->siapkan();

        Sanctum::actingAs($user);

        // Dua baris bernama persis sama, hanya berbeda kode dan lokasi.
        $this->imporCsv([
            'OBENG (-);Obeng;General Tools;3;A1;Boiler;3.1-BLR-17',
            'OBENG (-);Obeng;General Tools;7;A1;Instrument;3.1-INS-20',
        ]);

        $this->assertSame(2, Alat::query()->where('area_id', $area->id)->count());

        $boiler = Alat::query()->where('kode', '3.1-BLR-17')->firstOrFail();
        $instrument = Alat::query()->where('kode', '3.1-INS-20')->firstOrFail();

        $this->assertSame(3, (int) $boiler->total_aset);
        $this->assertSame('Boiler', $boiler->lokasi);
        $this->assertSame(7, (int) $instrument->total_aset);
        $this->assertSame('Instrument', $instrument->lokasi);
    }

    public function test_kode_yang_sudah_ada_memperbarui_alat_itu(): void
    {
        ['user' => $user, 'area' => $area] = $this->siapkan();

        $alat = Alat::create([
            'kode' => '3.1-BLR-17',
            'nama' => 'Obeng Min',
            'jenis_alat' => 'Obeng',
            'klasifikasi_alat' => 'General Tools',
            'total_aset' => 1,
            'area_id' => $area->id,
        ]);

        Sanctum::actingAs($user);

        // Nama pada berkas berbeda, tetapi kodenya sama sehingga alat yang sama diperbarui.
        $this->imporCsv(['OBENG (-);Obeng;General Tools;9;A1;Boiler;3.1-BLR-17']);

        $this->assertSame(1, Alat::query()->where('area_id', $area->id)->count());

        $alat->refresh();
        $this->assertSame('OBENG (-)', $alat->nama);
        $this->assertSame(9, (int) $alat->total_aset);
        $this->assertSame('Boiler', $alat->lokasi);
    }

    public function test_baris_tanpa_kode_tetap_dicocokkan_lewat_nama(): void
    {
        ['user' => $user, 'area' => $area] = $this->siapkan();

        $alat = Alat::create([
            'kode' => 'A1-1',
            'nama' => 'Kunci Inggris',
            'jenis_alat' => 'Kunci',
            'klasifikasi_alat' => 'General Tools',
            'total_aset' => 2,
            'area_id' => $area->id,
        ]);

        Sanctum::actingAs($user);

        $this->imporCsv(['Kunci Inggris;Kunci;General Tools;5;A1;Gudang;']);

        $this->assertSame(1, Alat::query()->where('area_id', $area->id)->count());

        $alat->refresh();
        $this->assertSame(5, (int) $alat->total_aset);
        // Kode lama dipertahankan karena kolom kode dikosongkan.
        $this->assertSame('A1-1', $alat->kode);
    }

    public function test_kode_kembar_dalam_satu_berkas_ditolak(): void
    {
        ['user' => $user] = $this->siapkan();

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/alats/import', [
            'file' => $this->buatBerkas([
                'Obeng A;Obeng;General Tools;1;A1;Boiler;3.1-BLR-17',
                'Obeng B;Obeng;General Tools;1;A1;Turbin;3.1-BLR-17',
            ]),
        ]);

        $response->assertStatus(422);
        $this->assertSame(0, Alat::query()->count());
    }

    private function imporCsv(array $baris): void
    {
        $this->postJson('/api/alats/import', ['file' => $this->buatBerkas($baris)])->assertAccepted();
    }

    private function buatBerkas(array $baris): UploadedFile
    {
        $isi = implode("\n", array_merge(
            ['Nama;Jenis Alat;Klasifikasi Alat;Total Aset;Area;Lokasi;Kode (opsional)'],
            $baris
        ));

        return UploadedFile::fake()->createWithContent('alat.csv', $isi);
    }

    /**
     * @return array{user: User, area: Area}
     */
    private function siapkan(): array
    {
        $role = Role::create(['key' => Role::KEY_PIC_TOOL, 'name' => 'PIC Tool']);
        $area = Area::create(['name' => 'Area 1', 'slug' => 'A1', 'kode' => 'A1']);
        $user = User::factory()->create([
            'role_id' => $role->id,
            'area_id' => $area->id,
        ]);

        return ['user' => $user, 'area' => $area];
    }
}
