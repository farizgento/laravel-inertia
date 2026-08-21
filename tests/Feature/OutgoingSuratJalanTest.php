<?php

namespace Tests\Feature;

use App\Models\Alat;
use App\Models\Area;
use App\Models\Peminjaman;
use App\Models\PeminjamanItem;
use App\Models\Role;
use App\Models\SuratJalan;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
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
        $this->assertSame('pengiriman', $document->jenis);
        $this->assertSame(1, $document->urutan);
        $expectedTemplateVersion = hash(
            'sha256',
            'shipment-subject-v1|'.hash_file('sha256', storage_path('templates/Surat-Jalan-Peminjaman.xlsx'))
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
        $this->assertSame('SURAT JALAN PENGIRIMAN', $main->getCell('B7')->getValue());
        $this->assertSame('PENGIRIMAN', $main->getCell('C12')->getValue());
        $this->assertSame(1, $main->getCell('B17')->getValue());
        $this->assertSame(11, $main->getCell('B27')->getValue());
        $this->assertSame(11, $main->getCell('E27')->getValue());
        $this->assertSame($expectedMainDrawingCount, $main->getDrawingCollection()->count());
        $this->assertSame('A1:H40', $main->getPageSetup()->getPrintArea());
        $this->assertSame('portrait', $main->getPageSetup()->getOrientation());
        $this->assertSame(1, $main->getPageSetup()->getFitToWidth());
        $this->assertSame(0, $main->getPageSetup()->getFitToHeight());
        $this->assertTrue($main->getStyle('C13')->getAlignment()->getWrapText());
        $this->assertTrue($main->getStyle('C27')->getAlignment()->getWrapText());

        $firstAnnex = $workbook->getSheetByName('LAMPIRAN FOTO');
        $secondAnnex = $workbook->getSheetByName('LAMPIRAN FOTO 2');
        $this->assertPhotoAnnex($firstAnnex, 4, ['C9', 'G9', 'C12', 'G12']);
        $this->assertPhotoAnnex($secondAnnex, 1, ['C9']);
        $this->assertStringContainsString('Halaman lampiran 1 dari 2', $firstAnnex->getCell('B18')->getValue());
        $this->assertStringContainsString('Halaman lampiran 2 dari 2', $secondAnnex->getCell('B18')->getValue());
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

        $this->replaceShipmentSubjectInWorkbook($generatedPath, 'PENGIRIMAN', 'IZIN KELUAR');
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
            'PENGIRIMAN',
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
        $this->assertSame($formulaLikeJob, $main->getCell('C13')->getValue());
        $this->assertSame(DataType::TYPE_STRING, $main->getCell('C13')->getDataType());
        $this->assertStringStartsWith($formulaLikeToolName, (string) $main->getCell('C17')->getValue());
        $this->assertSame(DataType::TYPE_STRING, $main->getCell('C17')->getDataType());
        $this->assertSame($formulaLikeToolType, $main->getCell('G17')->getValue());
        $this->assertSame(DataType::TYPE_STRING, $main->getCell('G17')->getDataType());

        $workbook->disconnectWorksheets();
    }

    public function test_return_flow_reloads_quantities_and_allocates_document_sequence(): void
    {
        Storage::fake('public');
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
                'surat_jalan' => UploadedFile::fake()->create(
                    "pengembalian-{$sequence}.pdf",
                    10,
                    'application/pdf'
                ),
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
            ->where('peminjaman_id', $loan->id)
            ->where('jenis', SuratJalan::TYPE_RETURN)
            ->orderBy('urutan')
            ->get();

        $this->assertSame([1, 2], $documents->pluck('urutan')->all());
        $this->assertSame(['pengembalian'], $documents->pluck('jenis')->unique()->values()->all());
        $this->assertSame(2, $item->fresh()->returned_qty);
        foreach ($documents as $document) {
            Storage::disk('public')->assertExists($document->path);
        }
    }

    public function test_legacy_outgoing_type_is_migrated_to_pengiriman(): void
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
                'jenis' => 'pengiriman',
            ]);
            $this->assertTrue(SuratJalan::query()->findOrFail($documentId)->isShipment());
        } finally {
            if (! $domainRestored) {
                DB::table('surat_jalan')
                    ->where('jenis', 'peminjaman_keluar')
                    ->update(['jenis' => 'pengiriman']);
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
        $this->assertSame(210.0, $sheet->getRowDimension(9)->getRowHeight());
        $this->assertSame(210.0, $sheet->getRowDimension(12)->getRowHeight());
        $this->assertEqualsWithDelta(42.0, $sheet->getColumnDimension('C')->getWidth(), 1.0);
        $this->assertEqualsWithDelta(42.0, $sheet->getColumnDimension('G')->getWidth(), 1.0);
        $this->assertSame('A1:I18', $sheet->getPageSetup()->getPrintArea());
        $this->assertSame('portrait', $sheet->getPageSetup()->getOrientation());
        $this->assertSame(1, $sheet->getPageSetup()->getFitToWidth());
        $this->assertSame(1, $sheet->getPageSetup()->getFitToHeight());
        $this->assertTrue($sheet->getStyle('B7')->getAlignment()->getWrapText());

        $drawings = $sheet->getDrawingCollection();
        $this->assertCount($drawingCount, $drawings);
        $this->assertSame($coordinates, collect($drawings)->map->getCoordinates()->all());

        foreach ($drawings as $drawing) {
            $this->assertGreaterThan(0, $drawing->getWidth());
            $this->assertGreaterThan(0, $drawing->getHeight());
            $this->assertLessThanOrEqual(270, $drawing->getWidth());
            $this->assertLessThanOrEqual(240, $drawing->getHeight());
            $this->assertGreaterThanOrEqual(16, $drawing->getOffsetX());
            $this->assertGreaterThanOrEqual(20, $drawing->getOffsetY());
        }
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
