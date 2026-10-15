<?php

namespace Tests\Feature;

use App\Models\Alat;
use App\Models\Area;
use App\Models\LaporanAlat;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class LaporanAlatMassalTest extends TestCase
{
    use RefreshDatabase;

    public function test_pic_tool_can_report_several_tools_at_once(): void
    {
        Storage::fake('public');
        ['user' => $user, 'alats' => $alats] = $this->siapkanArea();

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/laporan-kerusakan', [
            'deskripsi' => 'Kabel putus saat overhaul',
            'tanggal_laporan' => '2026-10-05',
            'foto' => $this->tigaFoto(),
            'items' => [
                ['alat_id' => $alats[0]->id, 'jumlah' => 2],
                ['alat_id' => $alats[1]->id, 'jumlah' => 1],
            ],
        ]);

        $response->assertCreated()->assertJsonPath('total', 2);

        $this->assertDatabaseCount('laporan_alats', 2);
        foreach ([[$alats[0]->id, 2], [$alats[1]->id, 1]] as [$alatId, $jumlah]) {
            $this->assertDatabaseHas('laporan_alats', [
                'alat_id' => $alatId,
                'jumlah' => $jumlah,
                'kategori' => LaporanAlat::CATEGORY_KERUSAKAN,
                'status' => LaporanAlat::STATUS_DILAPORKAN,
            ]);
        }

        $laporans = LaporanAlat::query()->with('photos')->get();
        // Foto dan tanggal pelaporan dipakai bersama oleh seluruh baris.
        $this->assertSame('2026-10-05', $laporans->first()->tanggal_laporan->toDateString());
        $this->assertCount(1, $laporans->pluck('path')->unique());
        Storage::disk('public')->assertExists($laporans->first()->path);

        foreach ($laporans as $laporan) {
            $this->assertCount(3, $laporan->photos);
            foreach ($laporan->photos as $photo) {
                Storage::disk('public')->assertExists($photo->path);
            }
        }
    }

    public function test_each_tool_can_have_its_own_photos(): void
    {
        Storage::fake('public');
        ['user' => $user, 'alats' => $alats] = $this->siapkanArea();

        Sanctum::actingAs($user);

        $this->post('/api/laporan-kerusakan', [
            'items' => [
                [
                    'alat_id' => $alats[0]->id,
                    'jumlah' => 1,
                    'deskripsi' => 'Layar retak',
                    'foto' => [
                        UploadedFile::fake()->image('megger-1.jpg'),
                        UploadedFile::fake()->image('megger-2.jpg'),
                        UploadedFile::fake()->image('megger-3.jpg'),
                    ],
                ],
                [
                    'alat_id' => $alats[1]->id,
                    'jumlah' => 1,
                    'deskripsi' => 'Gagang patah',
                    'foto' => [
                        UploadedFile::fake()->image('wrench-1.jpg'),
                        UploadedFile::fake()->image('wrench-2.jpg'),
                        UploadedFile::fake()->image('wrench-3.jpg'),
                        UploadedFile::fake()->image('wrench-4.jpg'),
                    ],
                ],
            ],
        ])->assertCreated();

        $megger = LaporanAlat::query()->with('photos')->where('alat_id', $alats[0]->id)->first();
        $wrench = LaporanAlat::query()->with('photos')->where('alat_id', $alats[1]->id)->first();

        $this->assertCount(3, $megger->photos);
        $this->assertCount(4, $wrench->photos);
        // Tiap alat memakai berkasnya sendiri, tidak saling tertukar.
        $this->assertSame(
            ['megger-1.jpg', 'megger-2.jpg', 'megger-3.jpg'],
            $megger->photos->pluck('original_name')->all()
        );
        $this->assertSame(
            ['wrench-1.jpg', 'wrench-2.jpg', 'wrench-3.jpg', 'wrench-4.jpg'],
            $wrench->photos->pluck('original_name')->all()
        );
    }

    public function test_general_photos_fill_tools_without_their_own(): void
    {
        Storage::fake('public');
        ['user' => $user, 'alats' => $alats] = $this->siapkanArea();

        Sanctum::actingAs($user);

        $this->post('/api/laporan-kerusakan', [
            'deskripsi' => 'Terendam banjir',
            'foto' => $this->tigaFoto(),
            'items' => [
                [
                    'alat_id' => $alats[0]->id,
                    'jumlah' => 1,
                    'foto' => [
                        UploadedFile::fake()->image('khusus-1.jpg'),
                        UploadedFile::fake()->image('khusus-2.jpg'),
                        UploadedFile::fake()->image('khusus-3.jpg'),
                    ],
                ],
                ['alat_id' => $alats[1]->id, 'jumlah' => 1],
            ],
        ])->assertCreated();

        $this->assertSame(
            ['khusus-1.jpg', 'khusus-2.jpg', 'khusus-3.jpg'],
            LaporanAlat::query()->with('photos')->where('alat_id', $alats[0]->id)->first()->photos->pluck('original_name')->all()
        );
        $this->assertSame(
            ['bukti-1.jpg', 'bukti-2.jpg', 'bukti-3.jpg'],
            LaporanAlat::query()->with('photos')->where('alat_id', $alats[1]->id)->first()->photos->pluck('original_name')->all()
        );
    }

    public function test_tool_photos_are_required_when_general_photos_are_empty(): void
    {
        Storage::fake('public');
        ['user' => $user, 'alats' => $alats] = $this->siapkanArea();

        Sanctum::actingAs($user);

        $this->postJson('/api/laporan-kerusakan', [
            'deskripsi' => 'Rusak',
            'items' => [
                [
                    'alat_id' => $alats[0]->id,
                    'jumlah' => 1,
                    'foto' => [
                        UploadedFile::fake()->image('khusus-1.jpg'),
                        UploadedFile::fake()->image('khusus-2.jpg'),
                        UploadedFile::fake()->image('khusus-3.jpg'),
                    ],
                ],
                ['alat_id' => $alats[1]->id, 'jumlah' => 1],
            ],
        ])->assertStatus(422)->assertJsonValidationErrors('items.1.foto');

        $this->postJson('/api/laporan-kerusakan', [
            'deskripsi' => 'Rusak',
            'items' => [
                [
                    'alat_id' => $alats[0]->id,
                    'jumlah' => 1,
                    'foto' => [
                        UploadedFile::fake()->image('khusus-1.jpg'),
                        UploadedFile::fake()->image('khusus-2.jpg'),
                    ],
                ],
            ],
        ])->assertStatus(422)->assertJsonValidationErrors('items.0.foto');

        $this->assertDatabaseCount('laporan_alats', 0);
    }

    public function test_at_least_three_photos_are_required(): void
    {
        Storage::fake('public');
        ['user' => $user, 'alats' => $alats] = $this->siapkanArea();

        Sanctum::actingAs($user);

        $this->postJson('/api/laporan-kerusakan', [
            'deskripsi' => 'Kurang foto',
            'foto' => [
                UploadedFile::fake()->image('bukti-1.jpg'),
                UploadedFile::fake()->image('bukti-2.jpg'),
            ],
            'items' => [['alat_id' => $alats[0]->id, 'jumlah' => 1]],
        ])->assertStatus(422)->assertJsonValidationErrors('foto');

        $this->postJson('/api/laporan-kerusakan', [
            'deskripsi' => 'Satu foto saja',
            'foto' => UploadedFile::fake()->image('bukti.jpg'),
            'items' => [['alat_id' => $alats[0]->id, 'jumlah' => 1]],
        ])->assertStatus(422)->assertJsonValidationErrors('foto');

        $this->assertDatabaseCount('laporan_alats', 0);
    }

    public function test_photo_list_is_returned_with_report(): void
    {
        Storage::fake('public');
        ['user' => $user, 'alats' => $alats] = $this->siapkanArea();

        Sanctum::actingAs($user);

        $this->postJson('/api/laporan-kerusakan', [
            'deskripsi' => 'Rusak berat',
            'foto' => $this->tigaFoto(),
            'items' => [['alat_id' => $alats[0]->id, 'jumlah' => 1]],
        ])->assertCreated();

        $this->getJson('/api/laporan-kerusakan')
            ->assertOk()
            ->assertJsonCount(3, '0.photos')
            ->assertJsonPath('0.photos.0.original_name', 'bukti-1.jpg');
    }

    public function test_each_tool_keeps_its_own_description(): void
    {
        Storage::fake('public');
        ['user' => $user, 'alats' => $alats] = $this->siapkanArea();

        Sanctum::actingAs($user);

        $this->postJson('/api/laporan-kerusakan', [
            'foto' => $this->tigaFoto(),
            'items' => [
                ['alat_id' => $alats[0]->id, 'jumlah' => 1, 'deskripsi' => 'Layar retak'],
                ['alat_id' => $alats[1]->id, 'jumlah' => 1, 'deskripsi' => 'Gagang patah'],
            ],
        ])->assertCreated();

        $this->assertDatabaseHas('laporan_alats', [
            'alat_id' => $alats[0]->id,
            'deskripsi' => 'Layar retak',
        ]);
        $this->assertDatabaseHas('laporan_alats', [
            'alat_id' => $alats[1]->id,
            'deskripsi' => 'Gagang patah',
        ]);
    }

    public function test_general_description_fills_tools_left_empty(): void
    {
        Storage::fake('public');
        ['user' => $user, 'alats' => $alats] = $this->siapkanArea();

        Sanctum::actingAs($user);

        $this->postJson('/api/laporan-kerusakan', [
            'deskripsi' => 'Terendam banjir',
            'foto' => $this->tigaFoto(),
            'items' => [
                ['alat_id' => $alats[0]->id, 'jumlah' => 1, 'deskripsi' => 'Kabel putus'],
                ['alat_id' => $alats[1]->id, 'jumlah' => 1],
            ],
        ])->assertCreated();

        $this->assertDatabaseHas('laporan_alats', [
            'alat_id' => $alats[0]->id,
            'deskripsi' => 'Kabel putus',
        ]);
        $this->assertDatabaseHas('laporan_alats', [
            'alat_id' => $alats[1]->id,
            'deskripsi' => 'Terendam banjir',
        ]);
    }

    public function test_description_is_required_per_tool_when_general_is_empty(): void
    {
        Storage::fake('public');
        ['user' => $user, 'alats' => $alats] = $this->siapkanArea();

        Sanctum::actingAs($user);

        $this->postJson('/api/laporan-kerusakan', [
            'foto' => $this->tigaFoto(),
            'items' => [
                ['alat_id' => $alats[0]->id, 'jumlah' => 1, 'deskripsi' => 'Kabel putus'],
                ['alat_id' => $alats[1]->id, 'jumlah' => 1],
            ],
        ])->assertStatus(422)->assertJsonValidationErrors('items.1.deskripsi');

        $this->assertDatabaseCount('laporan_alats', 0);
    }

    public function test_report_date_defaults_to_today_and_rejects_future_date(): void
    {
        Storage::fake('public');
        ['user' => $user, 'alats' => $alats] = $this->siapkanArea();

        Sanctum::actingAs($user);

        $this->postJson('/api/laporan-kehilangan', [
            'deskripsi' => 'Hilang di lokasi kerja',
            'foto' => $this->tigaFoto(),
            'items' => [['alat_id' => $alats[0]->id, 'jumlah' => 1]],
        ])->assertCreated();

        $this->assertSame(
            now()->toDateString(),
            LaporanAlat::query()->first()->tanggal_laporan->toDateString()
        );

        $this->postJson('/api/laporan-kehilangan', [
            'deskripsi' => 'Tanggal di masa depan',
            'tanggal_laporan' => now()->addDay()->toDateString(),
            'foto' => $this->tigaFoto(),
            'items' => [['alat_id' => $alats[0]->id, 'jumlah' => 1]],
        ])->assertStatus(422)->assertJsonValidationErrors('tanggal_laporan');
    }

    public function test_duplicate_and_empty_selection_are_rejected(): void
    {
        Storage::fake('public');
        ['user' => $user, 'alats' => $alats] = $this->siapkanArea();

        Sanctum::actingAs($user);

        $this->postJson('/api/laporan-kerusakan', [
            'deskripsi' => 'Tanpa alat',
            'foto' => $this->tigaFoto(),
            'items' => [],
        ])->assertStatus(422)->assertJsonValidationErrors('items');

        $this->postJson('/api/laporan-kerusakan', [
            'deskripsi' => 'Alat dobel',
            'foto' => $this->tigaFoto(),
            'items' => [
                ['alat_id' => $alats[0]->id, 'jumlah' => 1],
                ['alat_id' => $alats[0]->id, 'jumlah' => 2],
            ],
        ])->assertStatus(422);

        $this->assertDatabaseCount('laporan_alats', 0);
    }

    public function test_tool_from_other_area_cannot_be_reported(): void
    {
        Storage::fake('public');
        ['user' => $user] = $this->siapkanArea();

        $areaLain = Area::create(['name' => 'Area 2', 'slug' => 'area-2', 'kode' => 'A2']);
        $alatLuar = Alat::create([
            'nama' => 'Hydraulic Jack',
            'jenis_alat' => 'Alat Angkat',
            'klasifikasi_alat' => 'Mekanikal',
            'total_aset' => 4,
            'area_id' => $areaLain->id,
        ]);

        Sanctum::actingAs($user);

        $this->postJson('/api/laporan-kerusakan', [
            'deskripsi' => 'Alat area lain',
            'foto' => $this->tigaFoto(),
            'items' => [['alat_id' => $alatLuar->id, 'jumlah' => 1]],
        ])->assertStatus(422)->assertJsonValidationErrors('items');

        $this->assertDatabaseCount('laporan_alats', 0);
    }

    public function test_excel_upload_matches_tools_by_name_and_jenis(): void
    {
        ['user' => $user, 'alats' => $alats] = $this->siapkanArea();

        Sanctum::actingAs($user);

        $csv = implode("\n", [
            'Nama Alat;Jenis Alat;Jumlah',
            'megger;Alat Ukur;2',
            'Torque Wrench;;3',
            'Alat Entah Apa;;1',
            'Megger;Alat Ukur;',
        ]);

        $response = $this->post('/api/laporan-alat/cocokkan', [
            'file' => UploadedFile::fake()->createWithContent('daftar.csv', $csv),
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('ringkasan.alat_cocok', 2)
            ->assertJsonPath('ringkasan.baris_bermasalah', 1)
            ->assertJsonPath('items.0.id', $alats[0]->id)
            ->assertJsonPath('items.0.qty', 2)
            ->assertJsonPath('items.1.id', $alats[1]->id)
            ->assertJsonPath('items.1.qty', 3);

        $this->assertStringContainsString(
            'tidak ditemukan di area ini',
            $response->json('masalah.0.pesan')
        );
    }

    public function test_excel_quantity_is_capped_at_total_aset(): void
    {
        ['user' => $user, 'alats' => $alats] = $this->siapkanArea();

        Sanctum::actingAs($user);

        $csv = "Nama Alat;Jumlah\nMegger;99\n";

        $this->post('/api/laporan-alat/cocokkan', [
            'file' => UploadedFile::fake()->createWithContent('daftar.csv', $csv),
        ])
            ->assertOk()
            ->assertJsonPath('items.0.qty', $alats[0]->total_aset)
            ->assertJsonPath('ringkasan.baris_bermasalah', 1);
    }

    public function test_daftar_alat_download_returns_spreadsheet(): void
    {
        ['user' => $user] = $this->siapkanArea();

        Sanctum::actingAs($user);

        $response = $this->get('/api/laporan-alat/daftar-alat');

        $response->assertOk();
        $this->assertSame(
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            $response->headers->get('content-type')
        );
        $this->assertStringContainsString('DAFTAR-ALAT-LAPORAN', $response->headers->get('content-disposition'));
    }

    /**
     * @return array<int, UploadedFile>
     */
    private function tigaFoto(): array
    {
        return [
            UploadedFile::fake()->image('bukti-1.jpg'),
            UploadedFile::fake()->image('bukti-2.jpg'),
            UploadedFile::fake()->image('bukti-3.jpg'),
        ];
    }

    /**
     * @return array{user: User, alats: array<int, Alat>}
     */
    private function siapkanArea(): array
    {
        $role = Role::create(['key' => Role::KEY_PIC_TOOL, 'name' => 'PIC Tool']);
        $area = Area::create(['name' => 'Area 1', 'slug' => 'area-1', 'kode' => 'A1']);
        $user = User::factory()->create([
            'role_id' => $role->id,
            'area_id' => $area->id,
        ]);

        $alats = [
            Alat::create([
                'nama' => 'Megger',
                'jenis_alat' => 'Alat Ukur',
                'klasifikasi_alat' => 'Elektrikal',
                'total_aset' => 10,
                'area_id' => $area->id,
            ]),
            Alat::create([
                'nama' => 'Torque Wrench',
                'jenis_alat' => 'Alat Kerja',
                'klasifikasi_alat' => 'Mekanikal',
                'total_aset' => 5,
                'area_id' => $area->id,
            ]),
        ];

        return ['user' => $user, 'alats' => $alats];
    }
}
