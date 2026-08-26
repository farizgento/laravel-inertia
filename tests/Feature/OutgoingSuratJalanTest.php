<?php

namespace Tests\Feature;

use App\Models\Alat;
use App\Models\Area;
use App\Models\Peminjaman;
use App\Models\PeminjamanItem;
use App\Models\Role;
use App\Models\SuratJalan;
use App\Models\User;
use App\Services\OutgoingSuratJalanService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use Tests\TestCase;
use ZipArchive;

class OutgoingSuratJalanTest extends TestCase
{
    use RefreshDatabase;

    public function test_shipping_generates_private_xlsx_with_compressed_photos_and_is_idempotent(): void
    {
        Storage::fake('local');

        [$loan, $pic] = $this->makeApprovedLoan(itemCount: 11);
        Sanctum::actingAs($pic);

        $template = IOFactory::load(storage_path('templates/Surat-Jalan-Peminjaman.xlsx'));
        $expectedMainDrawingCount = $template
            ->getSheetByName('MASTER SJ UP SLA')
            ->getDrawingCollection()
            ->count();
        $template->disconnectWorksheets();

        $photos = [
            UploadedFile::fake()->image('photo-1.png', 1200, 800),
            UploadedFile::fake()->image('photo-2.jpg', 800, 1200),
            UploadedFile::fake()->image('photo-3.png', 1000, 1000),
            UploadedFile::fake()->image('photo-4.jpg', 1600, 900),
            UploadedFile::fake()->image('photo-5.png', 900, 1600),
        ];

        $response = $this->post('/api/pengiriman/'.$loan->id.'/kirim', [
            'pengirim_nama' => 'Kurir Lapangan',
            'photos' => $photos,
        ], ['Accept' => 'application/json']);

        $response
            ->assertOk()
            ->assertJsonPath('status', Peminjaman::STATUS_DIKIRIM)
            ->assertJsonPath(
                'surat_jalan_download_url',
                '/api/pengiriman/'.$loan->id.'/surat-jalan-peminjaman/download'
            );

        $loan->refresh();
        $this->assertSame(Peminjaman::STATUS_DIKIRIM, $loan->status);

        $document = SuratJalan::query()
            ->with('photos')
            ->where('peminjaman_id', $loan->id)
            ->where('jenis', SuratJalan::TYPE_SHIPMENT)
            ->firstOrFail();

        $this->assertSame('local', $document->disk);
        $this->assertSame('peminjaman', $document->jenis);
        $this->assertSame(1, $document->urutan);
        $expectedTemplateVersion = hash(
            'sha256',
            'shipment-subject-peminjaman-v1|'.hash_file('sha256', storage_path('templates/Surat-Jalan-Peminjaman.xlsx'))
        );
        $this->assertSame($expectedTemplateVersion, $document->template_version);
        $this->assertCount(5, $document->photos);
        Storage::disk('local')->assertExists($document->path);

        foreach ($document->photos as $photo) {
            $this->assertSame('image/jpeg', $photo->mime);
            $this->assertLessThanOrEqual(1600, $photo->width);
            $this->assertLessThanOrEqual(1600, $photo->height);
            Storage::disk('local')->assertExists($photo->path);
        }

        $workbook = IOFactory::load(Storage::disk('local')->path($document->path));
        $this->assertSame(3, $workbook->getSheetCount());

        $main = $workbook->getSheetByName('MASTER SJ UP SLA');
        $this->assertNotNull($main);
        $this->assertSame('SURAT JALAN PEMINJAMAN', $main->getCell('B7')->getValue());
        $this->assertSame('PEMINJAMAN', $main->getCell('C12')->getValue());
        // Baris "Nama Pengirim" disisipkan di bawah "Hal", sehingga Pekerjaan dan
        // seluruh tabel barang bergeser satu baris ke bawah.
        $this->assertSame('Nama Pengirim', $main->getCell('B13')->getValue());
        $this->assertSame('Kurir Lapangan', $main->getCell('C13')->getValue());
        $this->assertSame('Pekerjaan', $main->getCell('B14')->getValue());
        $this->assertSame(1, $main->getCell('B18')->getValue());
        $this->assertSame(11, $main->getCell('B28')->getValue());
        $this->assertSame(11, $main->getCell('E28')->getValue());
        // Nama barang tanpa tools ID, dan kolom keterangan dibiarkan kosong.
        $this->assertSame('Alat 1', $main->getCell('C18')->getValue());
        $this->assertSame('Alat 11', $main->getCell('C28')->getValue());
        $this->assertEmpty($main->getCell('G18')->getValue());
        $this->assertEmpty($main->getCell('G28')->getValue());
        $this->assertSame($expectedMainDrawingCount, $main->getDrawingCollection()->count());
        $this->assertSame('A1:H41', $main->getPageSetup()->getPrintArea());
        $this->assertA4PrintLayout($main, 0);
        $this->assertSame(28.0, $main->getRowDimension(7)->getRowHeight());
        $this->assertSame(22.0, $main->getRowDimension(8)->getRowHeight());
        $this->assertSame(16.0, $main->getRowDimension(9)->getRowHeight());
        $this->assertSame(9.0, $main->getStyle('B9')->getFont()->getSize());
        $this->assertGreaterThanOrEqual(30, $main->getRowDimension(13)->getRowHeight());
        $this->assertTrue($main->getStyle('B13')->getAlignment()->getWrapText());
        $this->assertTrue($main->getStyle('C13')->getAlignment()->getWrapText());
        $this->assertTrue($main->getStyle('C27')->getAlignment()->getWrapText());
        $this->assertSame(
            $main->getStyle('C12')->getFill()->getFillType(),
            $main->getStyle('C13')->getFill()->getFillType()
        );
        $this->assertSame(
            $main->getStyle('C12')->getFill()->getStartColor()->getARGB(),
            $main->getStyle('C13')->getFill()->getStartColor()->getARGB()
        );
        $this->assertSame(
            $main->getStyle('C12')->getFont()->getBold(),
            $main->getStyle('C13')->getFont()->getBold()
        );
        $this->assertSame(8.0, $main->getStyle('B41')->getFont()->getSize());

        $firstAnnex = $workbook->getSheetByName('LAMPIRAN FOTO');
        $secondAnnex = $workbook->getSheetByName('LAMPIRAN FOTO 2');
        $this->assertPhotoAnnex($firstAnnex, 4, ['C6', 'G6', 'C9', 'G9']);
        $this->assertPhotoAnnex($secondAnnex, 1, ['C6']);
        $this->assertStringContainsString('Halaman lampiran 1 dari 2', $firstAnnex->getCell('B15')->getValue());
        $this->assertStringContainsString('Halaman lampiran 2 dari 2', $secondAnnex->getCell('B15')->getValue());
        $this->assertWorkbookHasNoPlaceholders($workbook);
        $workbook->disconnectWorksheets();
        $generatedPath = Storage::disk('local')->path($document->path);
        $this->assertDrawingAspectLocks($generatedPath, 5);
        $this->assertTemplateMainShapesSurvive($generatedPath);

        $repeat = $this->postJson('/api/pengiriman/'.$loan->id.'/kirim');
        $repeat
            ->assertOk()
            ->assertJsonPath('status', Peminjaman::STATUS_DIKIRIM)
            ->assertJsonPath(
                'surat_jalan_download_url',
                '/api/pengiriman/'.$loan->id.'/surat-jalan-peminjaman/download'
            );
        $this->assertSame(1, SuratJalan::query()
            ->where('peminjaman_id', $loan->id)
            ->where('jenis', SuratJalan::TYPE_SHIPMENT)
            ->count());

        $this->replaceShipmentSubjectInWorkbook($generatedPath, 'PEMINJAMAN', 'IZIN KELUAR');
        $document->forceFill([
            'template_version' => hash_file('sha256', storage_path('templates/Surat-Jalan-Peminjaman.xlsx')),
        ])->saveQuietly();
        $legacyWorkbook = IOFactory::load($generatedPath);
        $this->assertSame(
            'IZIN KELUAR',
            $legacyWorkbook->getSheetByName('MASTER SJ UP SLA')->getCell('C12')->getValue()
        );
        $legacyWorkbook->disconnectWorksheets();
        $legacyDocumentPath = $document->path;

        $this->get('/api/pengiriman/'.$loan->id.'/surat-jalan-peminjaman/download')
            ->assertOk()
            ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        $document->refresh();
        $this->assertNotSame($legacyDocumentPath, $document->path);
        Storage::disk('local')->assertMissing($legacyDocumentPath);
        Storage::disk('local')->assertExists($document->path);
        $repairedPath = Storage::disk('local')->path($document->path);
        $repairedWorkbook = IOFactory::load($repairedPath);
        $this->assertSame(
            'PEMINJAMAN',
            $repairedWorkbook->getSheetByName('MASTER SJ UP SLA')->getCell('C12')->getValue()
        );
        $repairedWorkbook->disconnectWorksheets();
        $this->assertSame($expectedTemplateVersion, $document->template_version);
        $this->assertDrawingAspectLocks($repairedPath, 5);
        $this->assertTemplateMainShapesSurvive($repairedPath);

        Sanctum::actingAs($loan->user()->firstOrFail());
        $this->get('/api/pengiriman/'.$loan->id.'/surat-jalan-peminjaman/download')
            ->assertOk();

        $otherArea = Area::query()->create([
            'name' => 'Area Lain',
            'slug' => 'area-lain',
            'kode' => 'LAIN',
        ]);
        $otherPic = User::factory()->create([
            'role_id' => $pic->role_id,
            'area_id' => $otherArea->id,
        ]);
        Sanctum::actingAs($otherPic);
        $this->get('/api/pengiriman/'.$loan->id.'/surat-jalan-peminjaman/download')
            ->assertForbidden();
    }

    public function test_shipping_stores_original_jpg_or_png_when_image_driver_is_unavailable(): void
    {
        Storage::fake('local');
        $this->app->bind(OutgoingSuratJalanService::class, fn () => new class extends OutgoingSuratJalanService
        {
            protected function imageManager(): ?\Intervention\Image\ImageManager
            {
                return null;
            }
        });

        [$loan, $pic] = $this->makeApprovedLoan();
        Sanctum::actingAs($pic);

        $response = $this->post('/api/pengiriman/'.$loan->id.'/kirim', [
            'pengirim_nama' => 'Kurir Lapangan',
            'photos' => [UploadedFile::fake()->image('photo.png', 40, 40)],
        ], ['Accept' => 'application/json']);

        $response
            ->assertOk()
            ->assertJsonPath('status', Peminjaman::STATUS_DIKIRIM);

        $document = SuratJalan::query()
            ->with('photos')
            ->where('peminjaman_id', $loan->id)
            ->where('jenis', SuratJalan::TYPE_SHIPMENT)
            ->firstOrFail();

        $this->assertSame('image/png', $document->photos->first()->mime);
        $this->assertStringEndsWith('.png', $document->photos->first()->path);
        Storage::disk('local')->assertExists($document->path);
        Storage::disk('local')->assertExists($document->photos->first()->path);
    }

    public function test_shipping_requires_one_to_eight_images_and_does_not_change_status_on_validation_error(): void
    {
        Storage::fake('local');
        [$loan, $pic] = $this->makeApprovedLoan();
        Sanctum::actingAs($pic);

        $this->postJson('/api/pengiriman/'.$loan->id.'/kirim', [
            'pengirim_nama' => 'Kurir Lapangan',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['photos']);

        $this->assertSame(Peminjaman::STATUS_DISETUJUI, $loan->fresh()->status);
        $this->assertDatabaseMissing('surat_jalan', [
            'peminjaman_id' => $loan->id,
            'jenis' => SuratJalan::TYPE_SHIPMENT,
        ]);

        $tooManyPhotos = [];
        for ($index = 1; $index <= 9; $index++) {
            $tooManyPhotos[] = UploadedFile::fake()->image("photo-{$index}.jpg", 20, 20);
        }

        $this->post('/api/pengiriman/'.$loan->id.'/kirim', [
            'pengirim_nama' => 'Kurir Lapangan',
            'photos' => $tooManyPhotos,
        ], ['Accept' => 'application/json'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['photos']);

        $this->assertSame(Peminjaman::STATUS_DISETUJUI, $loan->fresh()->status);

        $this->post('/api/pengiriman/'.$loan->id.'/kirim', [
            'pengirim_nama' => 'Kurir Lapangan',
            'photos' => [UploadedFile::fake()->image('too-wide.png', 6001, 20)],
        ], ['Accept' => 'application/json'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['photos.0']);

        $this->assertSame(Peminjaman::STATUS_DISETUJUI, $loan->fresh()->status);

    }

    public function test_generic_admin_edit_cannot_transition_to_or_from_dikirim(): void
    {
        [$loan] = $this->makeApprovedLoan();
        $adminRole = Role::query()->create([
            'key' => Role::KEY_ADMIN,
            'name' => 'Admin',
        ]);
        $admin = User::factory()->create([
            'role_id' => $adminRole->id,
            'area_id' => $loan->area_id,
        ]);
        Sanctum::actingAs($admin);

        $this->putJson('/api/peminjaman/'.$loan->id, [
            'pekerjaan' => $loan->pekerjaan,
            'tanggal_pinjam' => $loan->tanggal_pinjam->toDateString(),
            'tanggal_kembali' => $loan->tanggal_kembali->toDateString(),
            'status' => Peminjaman::STATUS_DIKIRIM,
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['status']);

        $this->assertSame(Peminjaman::STATUS_DISETUJUI, $loan->fresh()->status);

        $this->putJson('/api/peminjaman/'.$loan->id, [
            'pekerjaan' => $loan->pekerjaan,
            'tanggal_pinjam' => $loan->tanggal_pinjam->toDateString(),
            'tanggal_kembali' => $loan->tanggal_kembali->toDateString(),
            'status' => Peminjaman::STATUS_DITERIMA,
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['status']);

        $this->assertSame(Peminjaman::STATUS_DISETUJUI, $loan->fresh()->status);

        $loan->update(['status' => Peminjaman::STATUS_DIKIRIM]);

        $this->putJson('/api/peminjaman/'.$loan->id, [
            'pekerjaan' => 'Metadata masih boleh diedit',
            'resi' => 'JNE-TEST-001',
            'tanggal_pinjam' => $loan->tanggal_pinjam->toDateString(),
            'tanggal_kembali' => $loan->tanggal_kembali->toDateString(),
            'status' => Peminjaman::STATUS_DIKIRIM,
        ])->assertOk();

        $this->putJson('/api/peminjaman/'.$loan->id, [
            'pekerjaan' => 'Tidak boleh keluar dari Dikirim',
            'tanggal_pinjam' => $loan->tanggal_pinjam->toDateString(),
            'tanggal_kembali' => $loan->tanggal_kembali->toDateString(),
            'status' => Peminjaman::STATUS_DISETUJUI,
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['status']);

        $loan->refresh();
        $this->assertSame(Peminjaman::STATUS_DIKIRIM, $loan->status);
        $this->assertSame('Metadata masih boleh diedit', $loan->pekerjaan);
        $this->assertSame('JNE-TEST-001', $loan->resi);
    }

    public function test_two_photo_annex_uses_full_height_columns_and_ten_item_table_stays_balanced(): void
    {
        Storage::fake('local');
        [$loan, $pic] = $this->makeApprovedLoan(itemCount: 10);
        Sanctum::actingAs($pic);

        $photos = [
            UploadedFile::fake()->image('portrait-1.jpg', 600, 1000),
            UploadedFile::fake()->image('portrait-2.jpg', 600, 1000),
        ];

        $this->post('/api/pengiriman/'.$loan->id.'/kirim', [
            'pengirim_nama' => 'Kurir Dua Foto',
            'photos' => $photos,
        ], ['Accept' => 'application/json'])->assertOk();

        $document = SuratJalan::query()
            ->where('peminjaman_id', $loan->id)
            ->where('jenis', SuratJalan::TYPE_SHIPMENT)
            ->firstOrFail();
        $workbook = IOFactory::load(Storage::disk('local')->path($document->path));
        $main = $workbook->getSheetByName('MASTER SJ UP SLA');

        $this->assertSame('A1:H40', $main->getPageSetup()->getPrintArea());
        $this->assertSame(10, $main->getCell('B27')->getValue());
        $this->assertStringStartsWith('Demikian surat jalan', (string) $main->getCell('B29')->getValue());
        $this->assertStringStartsWith('Dokumen ini dihasilkan', (string) $main->getCell('B40')->getValue());
        $this->assertA4PrintLayout($main, 0);
        $this->assertPhotoAnnex($workbook->getSheetByName('LAMPIRAN FOTO'), 2, ['C6', 'G6']);
        $this->assertWorkbookHasNoPlaceholders($workbook);

        $workbook->disconnectWorksheets();
    }

    public function test_three_photo_annex_centers_the_last_photo_and_compacts_unused_item_rows(): void
    {
        Storage::fake('local');
        [$loan, $pic] = $this->makeApprovedLoan();
        Sanctum::actingAs($pic);

        $photos = [
            UploadedFile::fake()->image('landscape-1.jpg', 1000, 600),
            UploadedFile::fake()->image('landscape-2.jpg', 1000, 600),
            UploadedFile::fake()->image('landscape-3.jpg', 1000, 600),
        ];

        $this->post('/api/pengiriman/'.$loan->id.'/kirim', [
            'pengirim_nama' => 'Kurir Tiga Foto',
            'photos' => $photos,
        ], ['Accept' => 'application/json'])->assertOk();

        $document = SuratJalan::query()
            ->where('peminjaman_id', $loan->id)
            ->where('jenis', SuratJalan::TYPE_SHIPMENT)
            ->firstOrFail();
        $workbook = IOFactory::load(Storage::disk('local')->path($document->path));
        $main = $workbook->getSheetByName('MASTER SJ UP SLA');

        $this->assertSame('A1:H31', $main->getPageSetup()->getPrintArea());
        $this->assertSame(1, $main->getCell('B18')->getValue());
        $this->assertStringStartsWith('Demikian surat jalan', (string) $main->getCell('B20')->getValue());
        $this->assertStringStartsWith('Dokumen ini dihasilkan', (string) $main->getCell('B31')->getValue());
        $this->assertA4PrintLayout($main, 0);
        $this->assertPhotoAnnex($workbook->getSheetByName('LAMPIRAN FOTO'), 3, ['C6', 'G6', 'C9']);
        $this->assertWorkbookHasNoPlaceholders($workbook);

        $workbook->disconnectWorksheets();
    }

    public function test_generated_text_is_stored_as_plain_text_instead_of_excel_formulas(): void
    {
        Storage::fake('local');
        [$loan, $pic] = $this->makeApprovedLoan();
        $formulaLikeJob = '=HYPERLINK("https://example.test","{{catatan pengguna}}")';
        $formulaLikeToolName = '=1+1';
        $formulaLikeToolType = '+SUM(1,1)';

        $loan->update(['pekerjaan' => $formulaLikeJob]);
        $tool = $loan->items()->with('alat')->firstOrFail()->alat;
        $tool->update([
            'nama' => $formulaLikeToolName,
            'jenis_alat' => $formulaLikeToolType,
        ]);

        Sanctum::actingAs($pic);
        $this->post('/api/pengiriman/'.$loan->id.'/kirim', [
            'pengirim_nama' => 'Kurir Lapangan',
            'photos' => [UploadedFile::fake()->image('photo.jpg', 40, 40)],
        ], ['Accept' => 'application/json'])->assertOk();

        $document = SuratJalan::query()
            ->where('peminjaman_id', $loan->id)
            ->where('jenis', SuratJalan::TYPE_SHIPMENT)
            ->firstOrFail();
        $workbook = IOFactory::load(Storage::disk('local')->path($document->path));
        $main = $workbook->getSheetByName('MASTER SJ UP SLA');

        $this->assertNotNull($main);
        $this->assertSame($formulaLikeJob, $main->getCell('C14')->getValue());
        $this->assertSame(DataType::TYPE_STRING, $main->getCell('C14')->getDataType());
        // Nama barang berisi nama alat saja, tanpa tools ID.
        $this->assertSame($formulaLikeToolName, $main->getCell('C18')->getValue());
        $this->assertSame(DataType::TYPE_STRING, $main->getCell('C18')->getDataType());
        // Kolom keterangan sengaja dikosongkan, jadi jenis alat tidak ikut tercetak.
        $this->assertEmpty($main->getCell('G18')->getValue());

        $workbook->disconnectWorksheets();
    }

    public function test_return_flow_reloads_quantities_and_allocates_document_sequence(): void
    {
        Storage::fake('local');
        [$loan] = $this->makeApprovedLoan();
        $item = $loan->items()->firstOrFail();
        $item->update([
            'qty' => 2,
            'approved_qty' => 2,
            'returned_qty' => 0,
        ]);
        $loan->update(['status' => Peminjaman::STATUS_DITERIMA]);
        Sanctum::actingAs($loan->user()->firstOrFail());

        foreach ([1, 2] as $sequence) {
            $response = $this->post('/api/pengiriman/'.$loan->id.'/kembalikan', [
                'pengirim_nama' => 'Pengembali Test',
                'photos' => [UploadedFile::fake()->image("pengembalian-{$sequence}.jpg", 40, 40)],
                'items' => [[
                    'item_id' => $item->id,
                    'returned_qty' => 1,
                ]],
            ], ['Accept' => 'application/json']);

            $response
                ->assertOk()
                ->assertJsonPath(
                    'status',
                    $sequence === 1
                        ? Peminjaman::STATUS_DIKEMBALIKAN_PARTIALS
                        : Peminjaman::STATUS_DIKEMBALIKAN_SEMUANYA
                );
        }

        $documents = SuratJalan::query()
            ->with('photos')
            ->where('peminjaman_id', $loan->id)
            ->where('jenis', SuratJalan::TYPE_RETURN)
            ->orderBy('urutan')
            ->get();

        $this->assertSame([1, 2], $documents->pluck('urutan')->all());
        $this->assertSame(['pengembalian'], $documents->pluck('jenis')->unique()->values()->all());
        $this->assertSame(['local'], $documents->pluck('disk')->unique()->values()->all());
        $this->assertSame(
            ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
            $documents->pluck('mime')->unique()->values()->all()
        );
        $this->assertSame(2, $item->fresh()->returned_qty);
        foreach ($documents as $document) {
            Storage::disk('local')->assertExists($document->path);
            $this->assertCount(1, $document->photos);
            Storage::disk('local')->assertExists($document->photos->first()->path);
            $this->assertStringStartsWith('SJ-PENGEMBALIAN/', $document->nomor);
            $this->assertSame(
                '/api/pengiriman/'.$loan->id.'/surat-jalan-pengembalian/'.$document->id.'/download',
                $document->download_url
            );

            $workbook = IOFactory::load(Storage::disk('local')->path($document->path));
            $main = $workbook->getSheetByName('MASTER SJ UP SLA');
            $this->assertSame('SURAT JALAN PENGEMBALIAN', $main->getCell('B7')->getValue());
            $this->assertSame('PENGEMBALIAN', $main->getCell('C12')->getValue());
            $this->assertSame('Nama Pengirim', $main->getCell('B13')->getValue());
            $this->assertSame(1, $main->getCell('E18')->getValue());
            $this->assertSame('A1:H31', $main->getPageSetup()->getPrintArea());
            $this->assertA4PrintLayout($main, 0);
            $this->assertPhotoAnnex($workbook->getSheetByName('LAMPIRAN FOTO'), 1, ['C6']);
            $workbook->disconnectWorksheets();

            $this->get($document->download_url)
                ->assertOk()
                ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        }
    }

    public function test_legacy_outgoing_type_is_migrated_to_peminjaman(): void
    {
        [$loan] = $this->makeApprovedLoan();
        $domainMigration = require database_path(
            'migrations/2026_08_21_000300_enforce_surat_jalan_type_domain.php'
        );
        $renameMigration = require database_path(
            'migrations/2026_08_21_000200_rename_outgoing_surat_jalan_type_to_pengiriman.php'
        );
        $domainMigration->down();
        $domainRestored = false;

        try {
            $documentId = DB::table('surat_jalan')->insertGetId([
                'peminjaman_id' => $loan->id,
                'pengirim_nama' => 'Pengirim Legacy',
                'jenis' => 'peminjaman_keluar',
                'urutan' => 1,
                'disk' => 'local',
                'path' => 'surat-jalan/legacy.xlsx',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $renameMigration->up();
            $domainMigration->up();
            $domainRestored = true;

            $this->assertDatabaseHas('surat_jalan', [
                'id' => $documentId,
                'jenis' => 'peminjaman',
            ]);
            $this->assertTrue(SuratJalan::query()->findOrFail($documentId)->isShipment());
        } finally {
            if (! $domainRestored) {
                DB::table('surat_jalan')
                    ->where('jenis', 'peminjaman_keluar')
                    ->update(['jenis' => 'peminjaman']);
                $domainMigration->up();
            }
        }
    }

    public function test_surat_jalan_type_domain_rejects_unknown_legacy_and_null_values(): void
    {
        [$loan] = $this->makeApprovedLoan();

        foreach (['lainnya', 'peminjaman_keluar', null] as $index => $type) {
            try {
                DB::table('surat_jalan')->insert([
                    'peminjaman_id' => $loan->id,
                    'pengirim_nama' => 'Pengirim Invalid',
                    'jenis' => $type,
                    'urutan' => $index + 1,
                    'disk' => 'local',
                    'path' => "surat-jalan/invalid-{$index}.xlsx",
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } catch (QueryException) {
                $this->addToAssertionCount(1);

                continue;
            }

            $this->fail('Database menerima jenis surat jalan yang tidak valid.');
        }
    }

    public function test_invalid_surat_jalan_type_is_not_exposed_as_a_return_document(): void
    {
        $document = new SuratJalan([
            'peminjaman_id' => 1,
            'jenis' => 'jenis-tidak-valid',
            'path' => 'surat-jalan/invalid.xlsx',
        ]);

        $this->assertFalse($document->isShipment());
        $this->assertFalse($document->isReturn());
        $this->assertNull($document->download_url);
    }

    private function assertPhotoAnnex($sheet, int $drawingCount, array $coordinates): void
    {
        $this->assertNotNull($sheet);
        // Baris No. Surat Jalan / ID Transaksi / Pekerjaan dihapus, sehingga slot foto
        // template (9 dan 12) bergeser menjadi 6 dan 9.
        $this->assertSame(210.0, $sheet->getRowDimension(6)->getRowHeight());
        $this->assertSame(210.0, $sheet->getRowDimension(9)->getRowHeight());
        $this->assertEqualsWithDelta(42.0, $sheet->getColumnDimension('C')->getWidth(), 1.0);
        $this->assertEqualsWithDelta(42.0, $sheet->getColumnDimension('G')->getWidth(), 1.0);
        $this->assertSame('A1:I15', $sheet->getPageSetup()->getPrintArea());
        $this->assertA4PrintLayout($sheet, 1);
        // Kepala lampiran tetap utuh, tetapi blok No. Surat Jalan / ID Transaksi /
        // Pekerjaan sudah tidak ada lagi di sheet ini.
        $this->assertSame('UNIT BISNIS PEMELIHARAAN', $sheet->getCell('B2')->getValue());
        $this->assertSame('LAMPIRAN FOTO BARANG', $sheet->getCell('B3')->getValue());

        $annexText = '';
        foreach ($sheet->getRowIterator() as $row) {
            $cells = $row->getCellIterator();
            $cells->setIterateOnlyExistingCells(true);
            foreach ($cells as $cell) {
                $annexText .= (string) $cell->getValue()."\n";
            }
        }
        $this->assertStringNotContainsString('No. Surat Jalan', $annexText);
        $this->assertStringNotContainsString('Pekerjaan:', $annexText);

        $drawings = $sheet->getDrawingCollection();
        $this->assertCount($drawingCount, $drawings);
        $this->assertSame($coordinates, collect($drawings)->map->getCoordinates()->all());

        if ($drawingCount === 1) {
            $this->assertSame('FFF3F4F6', $sheet->getStyle('D6')->getFill()->getStartColor()->getARGB());
            $this->assertSame(Border::BORDER_MEDIUM, $sheet->getStyle('D6')->getBorders()->getTop()->getBorderStyle());
            $this->assertSame(Border::BORDER_NONE, $sheet->getStyle('D6')->getBorders()->getBottom()->getBorderStyle());
            $this->assertSame(Border::BORDER_MEDIUM, $sheet->getStyle('D9')->getBorders()->getBottom()->getBorderStyle());
        } elseif ($drawingCount === 2) {
            $this->assertSame('FFFFFFFF', $sheet->getStyle('D6')->getFill()->getStartColor()->getARGB());
            $this->assertSame(Border::BORDER_NONE, $sheet->getStyle('C6')->getBorders()->getBottom()->getBorderStyle());
            $this->assertSame(Border::BORDER_MEDIUM, $sheet->getStyle('C9')->getBorders()->getBottom()->getBorderStyle());
        } elseif ($drawingCount === 3) {
            $this->assertSame('FFFFFFFF', $sheet->getStyle('D6')->getFill()->getStartColor()->getARGB());
            $this->assertSame(Border::BORDER_MEDIUM, $sheet->getStyle('C6')->getBorders()->getBottom()->getBorderStyle());
            $this->assertSame(Border::BORDER_MEDIUM, $sheet->getStyle('D9')->getBorders()->getTop()->getBorderStyle());
            $this->assertSame(Border::BORDER_MEDIUM, $sheet->getStyle('D9')->getBorders()->getBottom()->getBorderStyle());
        }

        $slots = match ($drawingCount) {
            1 => [[688, 592, 620, 520]],
            2 => [[302, 592, 270, 520], [302, 592, 270, 520]],
            3 => [[302, 280, 270, 240], [302, 280, 270, 240], [688, 280, 620, 240]],
            default => array_fill(0, 4, [302, 280, 270, 240]),
        };

        foreach ($drawings as $index => $drawing) {
            [$slotWidth, $slotHeight, $boxWidth, $boxHeight] = $slots[$index];
            $this->assertGreaterThan(0, $drawing->getWidth());
            $this->assertGreaterThan(0, $drawing->getHeight());
            $this->assertLessThanOrEqual($boxWidth, $drawing->getWidth());
            $this->assertLessThanOrEqual($boxHeight, $drawing->getHeight());
            $this->assertEqualsWithDelta(
                $slotWidth / 2,
                $drawing->getOffsetX() + ($drawing->getWidth() / 2),
                1.0
            );
            $this->assertEqualsWithDelta(
                $slotHeight / 2,
                $drawing->getOffsetY() + ($drawing->getHeight() / 2),
                1.0
            );
        }
    }

    private function assertA4PrintLayout($sheet, int $fitToHeight): void
    {
        $this->assertSame(PageSetup::PAPERSIZE_A4, $sheet->getPageSetup()->getPaperSize());
        $this->assertSame(PageSetup::ORIENTATION_PORTRAIT, $sheet->getPageSetup()->getOrientation());
        $this->assertSame(1, $sheet->getPageSetup()->getFitToWidth());
        $this->assertSame($fitToHeight, $sheet->getPageSetup()->getFitToHeight());
        // Scale 78/82 dari template sudah dihapus. Saat atribut scale tidak ditulis,
        // reader PhpSpreadsheet mengembalikannya sebagai default Excel (100).
        $this->assertSame(100, $sheet->getPageSetup()->getScale());
        $this->assertTrue($sheet->getPageSetup()->getFitToPage());
        $this->assertTrue($sheet->getPageSetup()->getHorizontalCentered());
        $this->assertEqualsWithDelta(0.35, $sheet->getPageMargins()->getLeft(), 0.001);
        $this->assertEqualsWithDelta(0.35, $sheet->getPageMargins()->getRight(), 0.001);
        $this->assertEqualsWithDelta(0.4, $sheet->getPageMargins()->getTop(), 0.001);
        $this->assertEqualsWithDelta(0.4, $sheet->getPageMargins()->getBottom(), 0.001);
    }

    private function replaceShipmentSubjectInWorkbook(string $path, string $from, string $to): void
    {
        $archive = new ZipArchive;
        $this->assertTrue($archive->open($path) === true);

        $sharedStrings = $archive->getFromName('xl/sharedStrings.xml');
        $this->assertIsString($sharedStrings);
        $needle = '<t>'.htmlspecialchars($from, ENT_XML1 | ENT_QUOTES, 'UTF-8').'</t>';
        $replacement = '<t>'.htmlspecialchars($to, ENT_XML1 | ENT_QUOTES, 'UTF-8').'</t>';
        $this->assertSame(1, substr_count($sharedStrings, $needle));
        $this->assertTrue($archive->addFromString(
            'xl/sharedStrings.xml',
            str_replace($needle, $replacement, $sharedStrings)
        ));
        $this->assertTrue($archive->close());
    }

    private function assertDrawingAspectLocks(string $path, int $expectedLocks): void
    {
        $archive = new ZipArchive;
        $this->assertTrue($archive->open($path) === true);
        $lockCount = 0;

        try {
            for ($index = 0; $index < $archive->numFiles; $index++) {
                $name = (string) $archive->getNameIndex($index);
                if (! preg_match('#^xl/drawings/drawing\d+\.xml$#', $name) || $name === 'xl/drawings/drawing1.xml') {
                    continue;
                }

                $xml = (string) $archive->getFromIndex($index);
                $lockCount += substr_count($xml, 'noChangeAspect="1"');
            }
        } finally {
            $archive->close();
        }

        $this->assertSame($expectedLocks, $lockCount);
    }

    private function assertTemplateMainShapesSurvive(string $path): void
    {
        $archive = new ZipArchive;
        $this->assertTrue($archive->open($path) === true);

        try {
            $mainDrawingXml = (string) $archive->getFromName('xl/drawings/drawing1.xml');
            $this->assertStringContainsString('name="Line 6"', $mainDrawingXml);
            $this->assertStringContainsString('name="Text Box 5"', $mainDrawingXml);
            $this->assertStringContainsString('UNIT BISNIS PEMELIHARAAN', $mainDrawingXml);
            $this->assertStringContainsString('<xdr:pic>', $mainDrawingXml);
        } finally {
            $archive->close();
        }
    }

    private function assertWorkbookHasNoPlaceholders(Spreadsheet $workbook): void
    {
        foreach ($workbook->getAllSheets() as $sheet) {
            foreach ($sheet->getRowIterator() as $row) {
                $cells = $row->getCellIterator();
                $cells->setIterateOnlyExistingCells(true);

                foreach ($cells as $cell) {
                    $this->assertDoesNotMatchRegularExpression(
                        '/\{\{[^}]+\}\}/',
                        (string) $cell->getValue(),
                        $sheet->getTitle().'!'.$cell->getCoordinate()
                    );
                }
            }
        }
    }

    /**
     * @return array{Peminjaman, User}
     */
    private function makeApprovedLoan(int $itemCount = 1): array
    {
        $picRole = Role::query()->create([
            'key' => Role::KEY_PIC_TOOL,
            'name' => 'PIC Tool',
        ]);
        $spRole = Role::query()->create([
            'key' => Role::KEY_SP_TOOL,
            'name' => 'SP Tool',
        ]);
        $userRole = Role::query()->create([
            'key' => Role::KEY_USER,
            'name' => 'User',
        ]);
        $area = Area::query()->create([
            'name' => 'UP Test',
            'slug' => 'up-test',
            'kode' => 'UPT',
        ]);
        $pic = User::factory()->create([
            'name' => 'PIC Test',
            'role_id' => $picRole->id,
            'area_id' => $area->id,
        ]);
        $reviewer = User::factory()->create([
            'name' => 'SP Test',
            'role_id' => $spRole->id,
            'area_id' => $area->id,
        ]);
        $borrower = User::factory()->create([
            'name' => 'Borrower Test',
            'role_id' => $userRole->id,
            'area_id' => $area->id,
        ]);
        $loan = Peminjaman::query()->create([
            'user_id' => $borrower->id,
            'area_id' => $area->id,
            'is_inter_area' => false,
            'status' => Peminjaman::STATUS_DISETUJUI,
            'kategori' => Peminjaman::KATEGORI_INTRA_AREA,
            'tanggal_pinjam' => now()->toDateString(),
            'tanggal_kembali' => now()->addDays(7)->toDateString(),
            'pekerjaan' => 'Pemeliharaan Unit Test',
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now()->subHour(),
        ]);

        for ($index = 1; $index <= $itemCount; $index++) {
            $tool = Alat::query()->create([
                'nama' => 'Alat '.$index,
                'jenis_alat' => 'Alat Ukur',
                'klasifikasi_alat' => 'Elektrikal',
                'total_aset' => 20,
                'area_id' => $area->id,
            ]);
            PeminjamanItem::query()->create([
                'peminjaman_id' => $loan->id,
                'alat_id' => $tool->id,
                'qty' => $index,
                'approved_qty' => $index,
                'review_status' => 'Disetujui',
            ]);
        }

        return [$loan, $pic];
    }
}
