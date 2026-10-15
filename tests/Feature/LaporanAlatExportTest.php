<?php

namespace Tests\Feature;

use App\Models\Alat;
use App\Models\Area;
use App\Models\LaporanAlat;
use App\Models\Role;
use App\Models\User;
use App\Services\LaporanAlatExcelService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Laravel\Sanctum\Sanctum;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Drawing as DrawingUnits;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Tests\TestCase;

class LaporanAlatExportTest extends TestCase
{
    use RefreshDatabase;

    private User $reporter;

    private Area $area;

    private Alat $tool;

    private array $exportFiles = [];

    private array $workbooks = [];

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $role = Role::create(['key' => Role::KEY_PIC_TOOL, 'name' => 'PIC Tool']);
        $this->area = Area::create(['name' => 'Area Jakarta', 'slug' => 'jakarta', 'kode' => 'JKT']);
        $this->reporter = User::factory()->create(['role_id' => $role->id, 'area_id' => $this->area->id]);
        $this->tool = Alat::create(['kode' => '001-JKT', 'nama' => 'Torque Wrench', 'jenis_alat' => 'Alat Kerja', 'klasifikasi_alat' => 'Mekanikal', 'total_aset' => 20, 'area_id' => $this->area->id]);
        Sanctum::actingAs($this->reporter);
    }

    protected function tearDown(): void
    {
        foreach ($this->workbooks as $workbook) {
            $workbook->disconnectWorksheets();
        }
        foreach ($this->exportFiles as $path) {
            if (is_file($path)) {
                unlink($path);
            }
        }
        parent::tearDown();
    }

    public function test_periods_use_report_dates_with_inclusive_bounds_and_legacy_fallback(): void
    {
        foreach (['kerusakan', 'kehilangan'] as $category) {
            $before = $this->report(['kategori' => $category, 'tanggal_laporan' => '2023-12-31']);
            $first = $this->report(['kategori' => $category, 'tanggal_laporan' => '2024-02-01', 'created_at' => '2026-10-01 12:00:00']);
            $leap = $this->report(['kategori' => $category, 'tanggal_laporan' => '2024-02-29']);
            $after = $this->report(['kategori' => $category, 'tanggal_laporan' => '2024-03-01']);
            $legacy = $this->report(['kategori' => $category, 'tanggal_laporan' => null, 'created_at' => '2024-02-29 23:59:59']);
            $nextYear = $this->report(['kategori' => $category, 'tanggal_laporan' => '2025-01-01']);

            $this->assertIds($category, ['period' => 'monthly', 'month' => 2, 'year' => 2024], [$first->id, $leap->id, $legacy->id]);
            $this->assertIds($category, ['period' => 'yearly', 'year' => 2024], [$first->id, $leap->id, $after->id, $legacy->id]);
            $this->assertIds($category, ['period' => 'custom', 'date_from' => '2024-02-29', 'date_to' => '2024-03-01'], [$leap->id, $after->id, $legacy->id]);
            $this->assertIds($category, ['period' => 'all'], [$before->id, $first->id, $leap->id, $after->id, $legacy->id, $nextYear->id]);
        }
    }

    public function test_invalid_periods_are_rejected_in_list_and_export(): void
    {
        foreach ([
            [['period' => 'monthly', 'year' => 2024, 'month' => 13], 'month'],
            [['period' => 'monthly', 'year' => 2024], 'month'],
            [['period' => 'yearly'], 'year'],
            [['period' => 'custom', 'date_from' => '2024-03-02', 'date_to' => '2024-03-01'], 'date_to'],
            [['period' => 'custom', 'date_from' => '2024-02-30', 'date_to' => '2024-03-01'], 'date_from'],
            [['period' => 'custom'], 'date_from'],
            [['period' => 'unknown'], 'period'],
        ] as [$filter, $error]) {
            $this->getJson('/api/laporan-kerusakan?'.http_build_query($filter))->assertUnprocessable()->assertJsonValidationErrors($error);
            $this->postJson('/api/laporan-kehilangan/export', $filter)->assertUnprocessable()->assertJsonValidationErrors($error);
        }
    }

    public function test_excel_matches_filters_ignores_pagination_and_embeds_photos_and_metadata(): void
    {
        $otherArea = Area::create(['name' => 'Area lain', 'slug' => 'lain', 'kode' => 'OTHER']);
        $photo = UploadedFile::fake()->image('bukti.png', 400, 250)->store('laporan', 'public');
        for ($i = 0; $i < 7; $i++) {
            $report = $this->report(['deskripsi' => '=Uraian aman '.$i, 'status' => 'Disetujui']);
            for ($p = 1; $p <= 4; $p++) {
                $report->photos()->create(['path' => $photo, 'urutan' => $p, 'original_name' => 'foto-'.$p.'.png']);
            }
        }
        $this->report(['area_id' => $otherArea->id, 'deskripsi' => 'Uraian aman RAHASIA AREA LAIN', 'status' => 'Disetujui']);
        $this->report(['tanggal_laporan' => '2026-09-30', 'deskripsi' => 'Uraian aman DI LUAR PERIODE', 'status' => 'Disetujui']);
        $this->report(['deskripsi' => 'Uraian aman STATUS TIDAK COCOK', 'status' => 'Ditolak']);
        $this->report(['deskripsi' => 'PENCARIAN TIDAK COCOK', 'status' => 'Disetujui']);
        $this->report(['kategori' => 'kehilangan', 'deskripsi' => 'Uraian aman KATEGORI LAIN', 'status' => 'Disetujui']);

        $filters = ['period' => 'monthly', 'month' => 10, 'year' => 2026, 'search' => 'Uraian aman', 'status' => 'Disetujui', 'alat_id' => $this->tool->id, 'area_id' => $otherArea->id];
        $this->getJson('/api/laporan-kerusakan?'.http_build_query($filters + ['per_page' => 1]))->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('meta.total', 7);

        $response = $this->postJson('/api/laporan-kerusakan/export', $filters + [
            'per_page' => 1, 'page' => 2,
            'nomor_laporan' => '007/LKT/X/2026', 'hasil_pemeriksaan' => '=1+1',
            'usulan' => 'Perbaiki pegangan alat.', 'tempat' => 'Jakarta', 'tanggal_cetak' => '2026-10-13',
            'penandatangan' => 'Petugas Pengendalian', 'jabatan' => 'Supervisor',
        ]);
        $workbook = $this->readExport($response);
        $sheet = $workbook->getActiveSheet();
        $values = json_encode($sheet->toArray());
        $this->assertSame('FORM LAPORAN KERUSAKAN TOOLS', $sheet->getCell('C2')->getValue());
        $this->assertSame('No : 007/LKT/X/2026', $sheet->getCell('A6')->getValue());
        $this->assertStringContainsString('Oktober 2026', $sheet->getCell('A7')->getValue());
        $this->assertStringContainsString('Area Jakarta', $sheet->getCell('A7')->getValue());
        $this->assertSame(7, $sheet->getCell('A22')->getValue());
        $this->assertSame('=Uraian aman 6', $sheet->getCell('C10')->getValue());
        $this->assertSame(DataType::TYPE_STRING, $sheet->getCell('C10')->getDataType());
        $this->assertSame('B. HASIL PEMERIKSAAN', $sheet->getCell('A26')->getValue());
        $this->assertSame('=1+1', $sheet->getCell('A27')->getValue());
        $this->assertSame(DataType::TYPE_STRING, $sheet->getCell('A27')->getDataType());
        $this->assertSame('Perbaiki pegangan alat.', $sheet->getCell('A31')->getValue());
        $this->assertSame('Petugas Pengendalian', $sheet->getCell('A38')->getValue());
        $this->assertCount(29, $sheet->getDrawingCollection()); // logo + 28 foto
        $this->assertSame('A1:E40', $sheet->getPageSetup()->getPrintArea());
        $this->assertStringContainsString('Halaman &P dari &N', $sheet->getHeaderFooter()->getOddFooter());
        foreach (['RAHASIA AREA LAIN', 'DI LUAR PERIODE', 'STATUS TIDAK COCOK', 'PENCARIAN TIDAK COCOK', 'KATEGORI LAIN', 'Fairus'] as $excluded) {
            $this->assertStringNotContainsString($excluded, $values);
        }
        $zip = new \ZipArchive;
        $zip->open($response->baseResponse->getFile()->getPathname());
        $this->assertNotFalse($zip->getFromName('xl/drawings/drawing1.xml'));
        $this->assertStringNotContainsString('TargetMode="External"', $zip->getFromName('xl/drawings/_rels/drawing1.xml.rels'));
        $zip->close();
    }

    public function test_legacy_missing_and_webp_photos_export_without_losing_reports(): void
    {
        $legacy = UploadedFile::fake()->image('legacy.jpg', 200, 100)->store('laporan', 'public');
        $this->report(['kategori' => 'kehilangan', 'path' => $legacy, 'deskripsi' => 'Foto lama']);
        $report = $this->report(['kategori' => 'kehilangan', 'deskripsi' => 'Foto hilang']);
        $report->photos()->create(['path' => 'laporan/missing.png', 'urutan' => 1]);
        $report->photos()->create(['path' => 'https://example.com/no-network.png', 'urutan' => 2]);
        $image = imagecreatetruecolor(200, 100);
        ob_start();
        imagewebp($image);
        $webp = ob_get_clean();
        imagedestroy($image);
        Storage::disk('public')->put('laporan/bukti.webp', $webp);
        $report->photos()->create(['path' => 'laporan/bukti.webp', 'urutan' => 3]);

        $sheet = $this->readExport($this->get('/api/laporan-kehilangan/export'))->getActiveSheet();
        $this->assertSame('FORM LAPORAN KEHILANGAN TOOLS', $sheet->getCell('C2')->getValue());
        $this->assertCount(3, $sheet->getDrawingCollection());
        $values = json_encode($sheet->toArray());
        $this->assertStringContainsString('Foto 1 tidak tersedia', $values);
        $this->assertStringContainsString('Foto 2 tidak tersedia', $values);
        $this->assertStringContainsString('Foto lama', $values);
        $this->assertStringContainsString('Foto hilang', $values);
    }

    public function test_empty_export_and_blank_templates_are_valid_and_have_no_sample_signature(): void
    {
        foreach (['kerusakan', 'kehilangan'] as $category) {
            $sheet = $this->readExport($this->get('/api/laporan-'.$category.'/export'))->getActiveSheet();
            $this->assertStringContainsString('Tidak ada laporan', $sheet->getCell('C10')->getValue());
            $this->assertCount(1, $sheet->getDrawingCollection());
            $template = $this->readExport($this->get('/api/laporan-'.$category.'/template'))->getActiveSheet();
            $this->assertSame('A1:E38', $template->getPageSetup()->getPrintArea());
            $this->assertSame('................................', $template->getCell('A36')->getValue());
            $this->assertNull($template->getCell('B10')->getValue());
        }
    }

    public function test_mixed_photo_orientations_use_fixed_frames_inside_the_attachment_column(): void
    {
        foreach (['kerusakan', 'kehilangan'] as $category) {
            $description = "Retak pada pegangan.\nPerlu penggantian komponen.";
            $report = $this->report(['kategori' => $category, 'deskripsi' => $description]);
            foreach ([[300, 900], [1200, 300], [600, 600]] as $index => [$width, $height]) {
                $path = UploadedFile::fake()->image('foto-'.$index.'.jpg', $width, $height)->store('laporan', 'public');
                $report->photos()->create(['path' => $path, 'urutan' => $index + 1]);
            }

            $workbook = $this->readExport($this->get('/api/laporan-'.$category.'/export'));
            $sheet = $workbook->getActiveSheet();
            $this->assertSame($description, $sheet->getCell('C10')->getValue());
            $photos = collect($sheet->getDrawingCollection())->filter(fn ($drawing) => str_starts_with($drawing->getName(), 'Laporan '))->values();
            $this->assertCount(3, $photos);
            $font = $workbook->getDefaultStyle()->getFont();
            $leftWidth = DrawingUnits::cellDimensionToPixels($sheet->getColumnDimension('D')->getWidth(), $font);
            $rightWidth = DrawingUnits::cellDimensionToPixels($sheet->getColumnDimension('E')->getWidth(), $font);
            $positions = [];
            foreach ($photos as $index => $drawing) {
                $this->assertSame(184, $drawing->getWidth());
                $this->assertSame(138, $drawing->getHeight());
                $this->assertSame('', $drawing->getCoordinates2()); // oneCellAnchor, ukuran tetap
                $column = substr($drawing->getCoordinates(), 0, 1);
                $row = (int) substr($drawing->getCoordinates(), 1);
                $this->assertSame(10 + intdiv($index, 2), $row);
                $this->assertContains($column, ['D', 'E']);
                $this->assertGreaterThanOrEqual(0, $drawing->getOffsetX());
                $this->assertLessThan($column === 'D' ? $leftWidth : $rightWidth, $drawing->getOffsetX());
                $x = ($column === 'E' ? $leftWidth : 0) + $drawing->getOffsetX();
                $this->assertLessThanOrEqual($leftWidth + $rightWidth, $x + $drawing->getWidth());
                $this->assertLessThanOrEqual(DrawingUnits::pointsToPixels($sheet->getRowDimension($row)->getRowHeight()), $drawing->getOffsetY() + $drawing->getHeight());
                $positions[] = $x;
                // Sumber tegak/mendatar/kotak memiliki kanvas sama; foto tidak terpotong.
                [$imageWidth, $imageHeight] = getimagesize($drawing->getPath());
                $this->assertSame([736, 552], [$imageWidth, $imageHeight]);
            }
            $this->assertSame(200, $positions[1] - $positions[0]); // lebar 184 + jarak 16
            $this->assertEqualsWithDelta(($leftWidth + $rightWidth - 184) / 2, $positions[2], 1); // foto ganjil di tengah
        }
    }

    public function test_user_without_report_access_cannot_export(): void
    {
        $role = Role::create(['key' => Role::KEY_USER, 'name' => 'User']);
        $this->reporter->update(['role_id' => $role->id]);
        Sanctum::actingAs($this->reporter->fresh());
        $this->getJson('/api/laporan-kerusakan/export')->assertForbidden();
        $this->postJson('/api/laporan-kehilangan/export')->assertForbidden();
    }

    public function test_long_inspection_and_suggestion_expand_without_losing_signature_or_print_area(): void
    {
        $inspection = str_repeat("Temuan pemeriksaan\n", 60);
        $suggestion = str_repeat("Saran perbaikan\n", 50);
        $sheet = $this->readExport($this->postJson('/api/laporan-kerusakan/export', [
            'hasil_pemeriksaan' => $inspection, 'usulan' => $suggestion, 'penandatangan' => 'Petugas Uji',
        ]))->getActiveSheet();
        // Middleware memangkas newline terakhir sebelum teks masuk ke exporter.
        $this->assertSame(trim($inspection), $sheet->getCell('A14')->getValue());
        $this->assertSame(trim($suggestion), $sheet->getCell('A24')->getValue());
        $this->assertSame('Petugas Uji', $sheet->getCell('A36')->getValue());
        $this->assertSame('A1:E38', $sheet->getPageSetup()->getPrintArea());
        foreach ($sheet->getRowDimensions() as $dimension) {
            $this->assertLessThanOrEqual(126, $dimension->getRowHeight());
        }
    }

    private function report(array $attributes = []): LaporanAlat
    {
        $report = new LaporanAlat([
            'kategori' => 'kerusakan', 'alat_id' => $this->tool->id, 'user_id' => $this->reporter->id,
            'area_id' => $this->area->id, 'jumlah' => 2, 'deskripsi' => 'Pegangan patah',
            'tanggal_laporan' => '2026-10-05', 'status' => 'Dilaporkan',
        ]);
        $report->forceFill($attributes)->save();

        return $report;
    }

    private function assertIds(string $category, array $filter, array $expected): void
    {
        $response = $this->getJson('/api/laporan-'.$category.'?'.http_build_query($filter + ['per_page' => 100]));
        $response->assertOk();
        $this->assertEqualsCanonicalizing($expected, array_column($response->json('data'), 'id'));
    }

    private function readExport(TestResponse $response): Spreadsheet
    {
        $response->assertOk()->assertHeader('Content-Type', LaporanAlatExcelService::MIME);
        $this->assertStringContainsString('.xlsx', $response->headers->get('Content-Disposition'));
        $path = $response->baseResponse->getFile()->getPathname();
        $this->exportFiles[] = $path;
        $workbook = IOFactory::load($path);
        $this->workbooks[] = $workbook;

        return $workbook;
    }
}
