<?php

namespace App\Services;

use App\Models\Peminjaman;
use App\Models\SuratJalan;
use App\Models\SuratJalanPhoto;
use App\Models\User;
use DOMDocument;
use DOMElement;
use DOMXPath;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;
use Intervention\Image\Drivers\Imagick\Driver as ImagickDriver;
use Intervention\Image\ImageManager;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use RuntimeException;
use Throwable;
use ZipArchive;

class OutgoingSuratJalanService
{
    public const MAX_PHOTOS = 8;

    public const MAX_IMAGE_DIMENSION = 1600;

    public const MAX_SOURCE_IMAGE_DIMENSION = 6000;

    private const PHOTOS_PER_PAGE = 4;

    private const TEMPLATE_ITEM_ROWS = 10;

    private const PHOTO_SLOT_WIDTH = 302;

    private const PHOTO_SLOT_HEIGHT = 280;

    private const PHOTO_BOX_WIDTH = 270;

    private const PHOTO_BOX_HEIGHT = 240;

    private const XLSX_MIME = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';

    private const SHIPMENT_SUBJECT = 'PENGIRIMAN';

    private const OUTPUT_VERSION = 'shipment-subject-v1';

    private const DOCUMENT_RELATIONSHIP_NAMESPACE = 'http://schemas.openxmlformats.org/officeDocument/2006/relationships';

    private const PACKAGE_RELATIONSHIP_NAMESPACE = 'http://schemas.openxmlformats.org/package/2006/relationships';

    /**
     * @param  array<int, UploadedFile>  $photos
     */
    public function ship(Peminjaman $peminjaman, User $actor, string $senderName, array $photos): SuratJalan
    {
        $storageDirectory = null;

        try {
            return DB::transaction(function () use ($peminjaman, $actor, $senderName, $photos, &$storageDirectory) {
                $lockedLoan = Peminjaman::query()
                    ->whereKey($peminjaman->getKey())
                    ->lockForUpdate()
                    ->firstOrFail();

                $existing = $lockedLoan->suratJalans()
                    ->where('jenis', SuratJalan::TYPE_SHIPMENT)
                    ->where('urutan', 1)
                    ->with('photos')
                    ->first();

                if ($existing && $this->isReady($existing)) {
                    if ($lockedLoan->status === Peminjaman::STATUS_DISETUJUI) {
                        $lockedLoan->update(['status' => Peminjaman::STATUS_DIKIRIM]);
                    }

                    if ($lockedLoan->status === Peminjaman::STATUS_DIKIRIM) {
                        return $existing;
                    }
                }

                if ($lockedLoan->status === Peminjaman::STATUS_DIKIRIM) {
                    throw ValidationException::withMessages([
                        'status' => ['Status sudah Dikirim, tetapi dokumen surat jalan belum lengkap. Hubungi administrator.'],
                    ]);
                }

                if ($lockedLoan->status !== Peminjaman::STATUS_DISETUJUI) {
                    throw ValidationException::withMessages([
                        'status' => ['Peminjaman tidak dalam status Disetujui.'],
                    ]);
                }

                if ($existing) {
                    throw ValidationException::withMessages([
                        'status' => ['Surat jalan pengiriman sudah tercatat tetapi belum lengkap. Hubungi administrator.'],
                    ]);
                }

                $lockedLoan->load([
                    'items' => fn ($query) => $query->where('approved_qty', '>', 0)->orderBy('id'),
                    'items.alat.area',
                    'user',
                    'area',
                    'requesterArea',
                    'reviewer',
                ]);

                if ($lockedLoan->items->isEmpty()) {
                    throw ValidationException::withMessages([
                        'items' => ['Tidak ada alat yang disetujui untuk dikirim.'],
                    ]);
                }

                $generatedAt = now();
                $documentNumber = $this->documentNumber($lockedLoan, $generatedAt);
                $storageDirectory = 'surat-jalan/'.$lockedLoan->id.'/pengiriman/'.Str::uuid();
                $storedPhotos = $this->compressAndStorePhotos($photos, $storageDirectory.'/photos');
                $templatePath = storage_path('templates/Surat-Jalan-Peminjaman.xlsx');

                if (! is_file($templatePath)) {
                    throw new RuntimeException('Template Surat-Jalan-Peminjaman.xlsx tidak ditemukan.');
                }

                $spreadsheet = $this->buildWorkbook(
                    $templatePath,
                    $lockedLoan,
                    $actor,
                    $generatedAt,
                    $documentNumber,
                    $storedPhotos
                );

                $filename = 'Surat-Jalan-Pengiriman-'.$lockedLoan->id.'.xlsx';
                $documentPath = $storageDirectory.'/'.$filename;
                $documentSize = $this->storeWorkbook($spreadsheet, $templatePath, $documentPath);

                $document = SuratJalan::query()->create([
                    'peminjaman_id' => $lockedLoan->id,
                    'pengirim_nama' => $senderName,
                    'jenis' => SuratJalan::TYPE_SHIPMENT,
                    'urutan' => 1,
                    'nomor' => $documentNumber,
                    'disk' => 'local',
                    'path' => $documentPath,
                    'original_name' => $filename,
                    'mime' => self::XLSX_MIME,
                    'size' => $documentSize,
                    'generated_by' => $actor->id,
                    'generated_at' => $generatedAt,
                    'template_version' => $this->templateVersion($templatePath),
                ]);

                foreach ($storedPhotos as $index => $photo) {
                    SuratJalanPhoto::query()->create([
                        'surat_jalan_id' => $document->id,
                        'urutan' => $index + 1,
                        'disk' => 'local',
                        'path' => $photo['path'],
                        'original_name' => $photo['original_name'],
                        'mime' => 'image/jpeg',
                        'size' => $photo['size'],
                        'width' => $photo['width'],
                        'height' => $photo['height'],
                    ]);
                }

                $lockedLoan->update(['status' => Peminjaman::STATUS_DIKIRIM]);

                return $document->load('photos');
            });
        } catch (Throwable $exception) {
            if ($storageDirectory) {
                $this->cleanupStorageDirectory($storageDirectory);
            }

            throw $exception;
        }
    }

    public function isReady(?SuratJalan $document): bool
    {
        if (! $document || ! $document->isShipment() || ! $document->path) {
            return false;
        }

        $disk = $document->disk ?: 'local';
        $photoCount = $document->relationLoaded('photos')
            ? $document->photos->count()
            : $document->photos()->count();

        return $document->mime === self::XLSX_MIME
            && $photoCount >= 1
            && $photoCount <= self::MAX_PHOTOS
            && Storage::disk($disk)->exists($document->path);
    }

    public function ensureCurrentShipmentSubject(SuratJalan $document): SuratJalan
    {
        if (! $this->canRepairShipmentWorkbook($document)) {
            return $document;
        }

        $templatePath = storage_path('templates/Surat-Jalan-Peminjaman.xlsx');
        $templateVersion = $this->templateVersion($templatePath);

        if ($document->template_version === $templateVersion) {
            return $document;
        }

        $replacementPath = null;
        $replacedPath = null;

        try {
            $currentDocument = DB::transaction(function () use (
                $document,
                $templateVersion,
                &$replacementPath,
                &$replacedPath
            ) {
                $lockedDocument = SuratJalan::query()
                    ->whereKey($document->getKey())
                    ->lockForUpdate()
                    ->firstOrFail();

                if (
                    ! $this->canRepairShipmentWorkbook($lockedDocument)
                    || $lockedDocument->template_version === $templateVersion
                ) {
                    return $lockedDocument;
                }

                $disk = Storage::disk('local');
                if (! $disk->exists($lockedDocument->path)) {
                    return $lockedDocument;
                }

                $sourcePath = $disk->path($lockedDocument->path);
                $spreadsheet = IOFactory::load($sourcePath);
                $mainSheet = $spreadsheet->getSheetByName('MASTER SJ UP SLA');

                if (! $mainSheet) {
                    $spreadsheet->disconnectWorksheets();
                    throw new RuntimeException('Sheet utama surat jalan pengiriman tidak ditemukan.');
                }

                if ($mainSheet->getCell('C12')->getValue() === self::SHIPMENT_SUBJECT) {
                    $spreadsheet->disconnectWorksheets();
                    $lockedDocument->forceFill(['template_version' => $templateVersion])->saveQuietly();

                    return $lockedDocument->refresh();
                }

                $mainSheet->getCell('C12')->setValueExplicit(self::SHIPMENT_SUBJECT, DataType::TYPE_STRING);
                $replacedPath = $lockedDocument->path;
                $replacementPath = $this->replacementDocumentPath($replacedPath);
                $size = $this->storeWorkbook($spreadsheet, $sourcePath, $replacementPath);
                $this->verifyStoredShipmentSubject($replacementPath, $size);

                $lockedDocument->forceFill([
                    'path' => $replacementPath,
                    'size' => $size,
                    'template_version' => $templateVersion,
                ])->saveQuietly();

                return $lockedDocument->refresh();
            });
        } catch (Throwable $exception) {
            if ($replacementPath) {
                $this->cleanupStorageFile(
                    $replacementPath,
                    $document->getKey(),
                    'repair_failed'
                );
            }

            throw $exception;
        }

        if ($replacementPath && $replacedPath && $currentDocument->path === $replacementPath) {
            $this->cleanupStorageFile(
                $replacedPath,
                $currentDocument->id,
                'replaced_document'
            );
        }

        return $currentDocument;
    }

    private function canRepairShipmentWorkbook(SuratJalan $document): bool
    {
        $isXlsx = $document->mime === self::XLSX_MIME
            || Str::endsWith(strtolower((string) $document->path), '.xlsx');

        return $document->exists
            && $document->isShipment()
            && (bool) $document->path
            && $isXlsx
            && ($document->disk ?: 'local') === 'local';
    }

    private function replacementDocumentPath(string $path): string
    {
        $directory = str_replace('\\', '/', dirname($path));
        $prefix = $directory === '.' ? '' : $directory.'/';

        return $prefix.Str::uuid().'-'.basename($path);
    }

    private function verifyStoredShipmentSubject(string $path, int $expectedSize): void
    {
        $disk = Storage::disk('local');
        if (! $disk->exists($path) || $disk->size($path) !== $expectedSize) {
            throw new RuntimeException('File hasil perbaikan surat jalan tidak tersimpan dengan utuh.');
        }

        $spreadsheet = IOFactory::load($disk->path($path));

        try {
            $mainSheet = $spreadsheet->getSheetByName('MASTER SJ UP SLA');
            if (! $mainSheet || $mainSheet->getCell('C12')->getValue() !== self::SHIPMENT_SUBJECT) {
                throw new RuntimeException('Nilai Hal pada surat jalan pengiriman gagal diperbaiki.');
            }
        } finally {
            $spreadsheet->disconnectWorksheets();
        }
    }

    private function cleanupStorageFile(string $path, int|string|null $documentId, string $reason): void
    {
        $disk = Storage::disk('local');
        $lastError = null;

        for ($attempt = 1; $attempt <= 3; $attempt++) {
            try {
                if (! $disk->exists($path) || $disk->delete($path)) {
                    return;
                }
            } catch (Throwable $cleanupException) {
                $lastError = $cleanupException->getMessage();
            }
        }

        Log::warning('File surat jalan gagal dibersihkan setelah perbaikan.', [
            'surat_jalan_id' => $documentId,
            'path' => $path,
            'reason' => $reason,
            'attempts' => 3,
            'error' => $lastError,
        ]);
    }

    private function templateVersion(string $templatePath): string
    {
        $hash = hash_file('sha256', $templatePath);
        if ($hash === false) {
            throw new RuntimeException('Versi template surat jalan tidak dapat dihitung.');
        }

        return hash('sha256', self::OUTPUT_VERSION.'|'.$hash);
    }

    /**
     * @param  array<int, UploadedFile>  $photos
     * @return array<int, array{path: string, original_name: string, size: int, width: int, height: int}>
     */
    private function compressAndStorePhotos(array $photos, string $directory): array
    {
        $manager = $this->imageManager();
        $disk = Storage::disk('local');
        $stored = [];

        foreach (array_values($photos) as $index => $file) {
            if (! $file instanceof UploadedFile) {
                throw ValidationException::withMessages([
                    "photos.$index" => ['Foto pengiriman tidak valid.'],
                ]);
            }

            $image = $manager->read($file->getRealPath())
                ->orient()
                ->scaleDown(self::MAX_IMAGE_DIMENSION, self::MAX_IMAGE_DIMENSION)
                ->resizeCanvas(
                    width: null,
                    height: null,
                    background: 'ffffff',
                    position: 'center'
                );
            $encoded = $image->toJpeg(quality: 78);
            $path = $directory.'/'.str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT).'-'.Str::uuid().'.jpg';

            if (! $disk->put($path, (string) $encoded)) {
                throw new RuntimeException('Foto pengiriman gagal disimpan.');
            }

            $stored[] = [
                'path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'size' => (int) $encoded->size(),
                'width' => $image->width(),
                'height' => $image->height(),
            ];
        }

        return $stored;
    }

    private function imageManager(): ImageManager
    {
        if (extension_loaded('imagick')) {
            return new ImageManager(new ImagickDriver);
        }

        if (extension_loaded('gd')) {
            return new ImageManager(new GdDriver);
        }

        throw new RuntimeException('Ekstensi Imagick atau GD diperlukan untuk memproses foto pengiriman.');
    }

    /**
     * @param  array<int, array{path: string, original_name: string, size: int, width: int, height: int}>  $photos
     */
    private function buildWorkbook(
        string $templatePath,
        Peminjaman $loan,
        User $actor,
        $generatedAt,
        string $documentNumber,
        array $photos
    ): Spreadsheet {
        $spreadsheet = IOFactory::load($templatePath);
        $mainSheet = $spreadsheet->getSheetByName('MASTER SJ UP SLA');
        $annexTemplate = $spreadsheet->getSheetByName('LAMPIRAN FOTO');

        if (! $mainSheet || ! $annexTemplate) {
            $spreadsheet->disconnectWorksheets();
            throw new RuntimeException('Struktur sheet template surat jalan tidak sesuai.');
        }

        $pageCount = (int) ceil(count($photos) / self::PHOTOS_PER_PAGE);
        $annexSheets = [$annexTemplate];

        for ($page = 2; $page <= $pageCount; $page++) {
            $clone = clone $annexTemplate;
            $clone->setTitle('LAMPIRAN FOTO '.$page);
            $spreadsheet->addSheet($clone);
            $annexSheets[] = $clone;
        }

        $recipient = trim((string) ($loan->user?->name ?? '-'));
        if ($loan->is_inter_area && $loan->requesterArea?->name) {
            $recipient .= ' - '.$loan->requesterArea->name;
        }

        $placeholders = [
            '{{nomor_surat_jalan}}' => $documentNumber,
            '{{id_transaksi}}' => (string) $loan->id,
            '{{waktu_cetak}}' => $generatedAt->format('d/m/Y H:i'),
            '{{penerima_atau_keamanan}}' => $recipient,
            '{{area_asal}}' => (string) ($loan->area?->name ?? '-'),
            '{{izin masuk/keluar}}' => self::SHIPMENT_SUBJECT,
            '{{nama_pekerjaan}}' => (string) $loan->pekerjaan,
            '{{nama_user}}' => (string) ($loan->user?->name ?? '-'),
            '{{tanggal_approved_user}}' => $this->formatDateTime($loan->created_at),
            '{{nama_sp_tools}}' => (string) ($loan->reviewer?->name ?? '-'),
            '{{tanggal_approved_sp_tools}}' => $this->formatDateTime($loan->reviewed_at),
            '{{nama_pic_tools}}' => (string) $actor->name,
            '{{tanggal_approved_pic_tools}}' => $generatedAt->format('d/m/Y H:i'),
            '{{foto_1}}' => '',
            '{{foto_2}}' => '',
            '{{foto_3}}' => '',
            '{{foto_4}}' => '',
        ];

        $this->assertTemplatePlaceholdersAreKnown($spreadsheet, $placeholders);
        $this->replacePlaceholders($spreadsheet, $placeholders);
        $mainSheet->getCell('B7')->setValueExplicit('SURAT JALAN PENGIRIMAN', DataType::TYPE_STRING);
        $this->applyWrappedRowHeight($mainSheet, 'C10:G10', 10, $recipient, 100, 20, 4);
        $this->applyWrappedRowHeight(
            $mainSheet,
            'C11:G11',
            11,
            (string) ($loan->area?->name ?? '-'),
            100,
            20,
            4
        );
        $this->applyWrappedRowHeight(
            $mainSheet,
            'C13:G13',
            13,
            (string) $loan->pekerjaan,
            100,
            20,
            12
        );
        $this->populateApprovedItems($mainSheet, $loan);

        foreach ($annexSheets as $pageIndex => $sheet) {
            $page = $pageIndex + 1;
            $pagePhotos = array_slice($photos, $pageIndex * self::PHOTOS_PER_PAGE, self::PHOTOS_PER_PAGE);
            $this->populatePhotoPage($sheet, $pagePhotos, $page, $pageCount, $documentNumber, $loan);
        }

        $spreadsheet->setActiveSheetIndex(0);

        return $spreadsheet;
    }

    private function replacePlaceholders(Spreadsheet $spreadsheet, array $placeholders): void
    {
        foreach ($spreadsheet->getAllSheets() as $sheet) {
            foreach ($sheet->getRowIterator() as $row) {
                $cells = $row->getCellIterator();
                $cells->setIterateOnlyExistingCells(true);

                foreach ($cells as $cell) {
                    $value = $cell->getValue();
                    if (is_string($value) && Str::contains($value, '{{')) {
                        $cell->setValueExplicit(strtr($value, $placeholders), DataType::TYPE_STRING);
                    }
                }
            }
        }
    }

    private function populateApprovedItems(Worksheet $sheet, Peminjaman $loan): void
    {
        $itemCount = $loan->items->count();
        $extraRows = max($itemCount - self::TEMPLATE_ITEM_ROWS, 0);

        if ($extraRows > 0) {
            $sheet->insertNewRowBefore(27, $extraRows);
            for ($row = 27; $row < 27 + $extraRows; $row++) {
                $sheet->duplicateStyle($sheet->getStyle('B26:G26'), "B{$row}:G{$row}");
                $sheet->getRowDimension($row)->setRowHeight($sheet->getRowDimension(26)->getRowHeight());
                $merge = "C{$row}:D{$row}";
                if (! in_array($merge, $sheet->getMergeCells(), true)) {
                    $sheet->mergeCells($merge);
                }
            }
        }

        $lastPreparedRow = 26 + $extraRows;
        for ($row = 17; $row <= $lastPreparedRow; $row++) {
            foreach (['B', 'C', 'E', 'F', 'G'] as $column) {
                $sheet->setCellValue($column.$row, null);
            }
        }

        foreach ($loan->items->values() as $index => $item) {
            $row = 17 + $index;
            $toolName = trim((string) ($item->alat?->nama ?? '-'));
            $toolCode = trim((string) ($item->alat?->kode ?? '-'));

            $sheet->setCellValue('B'.$row, $index + 1);
            $sheet->getCell('C'.$row)->setValueExplicit($toolName.' / '.$toolCode, DataType::TYPE_STRING);
            $sheet->setCellValue('E'.$row, (int) $item->approved_qty);
            $sheet->getCell('F'.$row)->setValueExplicit('Unit', DataType::TYPE_STRING);
            $sheet->getCell('G'.$row)->setValueExplicit(
                (string) ($item->alat?->jenis_alat ?? ''),
                DataType::TYPE_STRING
            );

            $toolDescription = $toolName.' / '.$toolCode;
            $toolType = (string) ($item->alat?->jenis_alat ?? '');
            $lineCount = max(
                1,
                (int) ceil(mb_strlen($toolDescription) / 48),
                (int) ceil(mb_strlen($toolType) / 28)
            );
            $sheet->getStyle("C{$row}:D{$row}")->getAlignment()->setWrapText(true);
            $sheet->getStyle('G'.$row)->getAlignment()->setWrapText(true);
            $sheet->getRowDimension($row)->setRowHeight(18 + (($lineCount - 1) * 15));
        }

        $sheet->getPageSetup()
            ->setPaperSize(PageSetup::PAPERSIZE_LETTER)
            ->setOrientation(PageSetup::ORIENTATION_PORTRAIT)
            ->setFitToPage(true)
            ->setFitToWidth(1)
            ->setFitToHeight(0);
        $sheet->getPageSetup()->setPrintArea('A1:H'.(39 + $extraRows));
    }

    /**
     * @param  array<int, array{path: string, original_name: string, size: int, width: int, height: int}>  $photos
     */
    private function populatePhotoPage(
        Worksheet $sheet,
        array $photos,
        int $page,
        int $pageCount,
        string $documentNumber,
        Peminjaman $loan
    ): void {
        $slots = ['C9', 'G9', 'C12', 'G12'];

        foreach ($slots as $slot) {
            $sheet->setCellValue($slot, null);
        }

        $sheet->getCell('B6')->setValueExplicit($documentNumber, DataType::TYPE_STRING);
        $sheet->getCell('F6')->setValueExplicit((string) $loan->id, DataType::TYPE_STRING);
        $annexJob = 'Pekerjaan: '.Str::limit((string) $loan->pekerjaan, 240);
        $sheet->getCell('B7')->setValueExplicit($annexJob, DataType::TYPE_STRING);
        $this->applyWrappedRowHeight($sheet, 'B7:H7', 7, $annexJob, 100, 18, 4);
        $sheet->getCell('B16')->setValueExplicit(
            'Lampiran ini merupakan bagian yang tidak terpisahkan dari Surat Jalan No. '.$documentNumber.'.',
            DataType::TYPE_STRING
        );
        $sheet->getCell('B18')->setValueExplicit(
            'Dokumen dihasilkan otomatis oleh aplikasi | ID Transaksi: '.$loan->id
            .' | Halaman lampiran '.$page.' dari '.$pageCount,
            DataType::TYPE_STRING
        );

        $sheet->getRowDimension(9)->setRowHeight(210);
        $sheet->getRowDimension(12)->setRowHeight(210);
        $sheet->getPageSetup()
            ->setPaperSize(PageSetup::PAPERSIZE_LETTER)
            ->setOrientation(PageSetup::ORIENTATION_PORTRAIT)
            ->setFitToPage(true)
            ->setFitToWidth(1)
            ->setFitToHeight(1);
        $sheet->getPageSetup()->setPrintArea('A1:I18');

        foreach ($photos as $index => $photo) {
            $drawing = new Drawing;
            $drawing->setName('Foto Pengiriman '.(($page - 1) * self::PHOTOS_PER_PAGE + $index + 1));
            $drawing->setDescription($photo['original_name']);
            $drawing->setPath(Storage::disk('local')->path($photo['path']));
            $drawing->setCoordinates($slots[$index]);
            $drawing->setResizeProportional(true);
            $drawing->setWidthAndHeight(self::PHOTO_BOX_WIDTH, self::PHOTO_BOX_HEIGHT);
            $drawing->setOffsetX(
                max((int) floor((self::PHOTO_SLOT_WIDTH - $drawing->getWidth()) / 2), 0)
            );
            $drawing->setOffsetY(
                max((int) floor((self::PHOTO_SLOT_HEIGHT - $drawing->getHeight()) / 2), 0)
            );
            $drawing->setWorksheet($sheet);
        }
    }

    private function assertTemplatePlaceholdersAreKnown(Spreadsheet $spreadsheet, array $placeholders): void
    {
        foreach ($spreadsheet->getAllSheets() as $sheet) {
            foreach ($sheet->getRowIterator() as $row) {
                $cells = $row->getCellIterator();
                $cells->setIterateOnlyExistingCells(true);

                foreach ($cells as $cell) {
                    $value = $cell->getValue();
                    if (! is_string($value) || ! preg_match_all('/\{\{[^}]+\}\}/', $value, $matches)) {
                        continue;
                    }

                    foreach ($matches[0] as $placeholder) {
                        if (! array_key_exists($placeholder, $placeholders)) {
                            throw new RuntimeException(
                                "Placeholder {$placeholder} belum dikenali pada "
                                ."{$sheet->getTitle()}!{$cell->getCoordinate()}."
                            );
                        }
                    }
                }
            }
        }
    }

    private function cleanupStorageDirectory(string $directory): void
    {
        $disk = Storage::disk('local');
        $lastError = null;

        for ($attempt = 1; $attempt <= 3; $attempt++) {
            try {
                if (! $disk->directoryExists($directory) || $disk->deleteDirectory($directory)) {
                    return;
                }
            } catch (Throwable $cleanupException) {
                $lastError = $cleanupException->getMessage();
            }
        }

        Log::warning('Folder surat jalan privat gagal dibersihkan setelah transaksi dibatalkan.', [
            'disk' => 'local',
            'directory' => $directory,
            'attempts' => 3,
            'error' => $lastError,
        ]);
    }

    private function applyWrappedRowHeight(
        Worksheet $sheet,
        string $range,
        int $row,
        string $text,
        int $charactersPerLine,
        float $minimumHeight,
        int $maximumLines
    ): void {
        $lineCount = min(
            max((int) ceil(max(mb_strlen($text), 1) / $charactersPerLine), 1),
            $maximumLines
        );

        $sheet->getStyle($range)->getAlignment()->setWrapText(true);
        $sheet->getRowDimension($row)->setRowHeight(
            $minimumHeight + (($lineCount - 1) * 15)
        );
    }

    private function storeWorkbook(Spreadsheet $spreadsheet, string $templatePath, string $path): int
    {
        $temporaryPath = tempnam(sys_get_temp_dir(), 'surat-jalan-');
        if ($temporaryPath === false) {
            $spreadsheet->disconnectWorksheets();
            throw new RuntimeException('File sementara surat jalan tidak dapat dibuat.');
        }

        try {
            IOFactory::createWriter($spreadsheet, 'Xlsx')->save($temporaryPath);
            $this->restoreTemplateMainDrawing($templatePath, $temporaryPath);
            $stream = fopen($temporaryPath, 'rb');
            if ($stream === false) {
                throw new RuntimeException('File surat jalan tidak dapat dibaca untuk disimpan.');
            }

            try {
                if (! Storage::disk('local')->put($path, $stream)) {
                    throw new RuntimeException('File surat jalan gagal disimpan.');
                }
            } finally {
                fclose($stream);
            }

            return (int) filesize($temporaryPath);
        } finally {
            $spreadsheet->disconnectWorksheets();
            @unlink($temporaryPath);
        }
    }

    private function restoreTemplateMainDrawing(string $templatePath, string $generatedPath): void
    {
        $template = new ZipArchive;
        $generated = new ZipArchive;

        if ($template->open($templatePath) !== true) {
            throw new RuntimeException('Template surat jalan tidak dapat dibuka untuk mempertahankan elemen visual.');
        }

        try {
            if ($generated->open($generatedPath) !== true) {
                throw new RuntimeException('Surat jalan hasil generasi tidak dapat dibuka untuk mempertahankan elemen visual.');
            }

            try {
                $templateParts = $this->locateMainDrawingParts($template);
                $generatedParts = $this->locateMainDrawingParts($generated);
                $templateDrawing = $this->zipEntry($template, $templateParts['drawing']);
                $templateRelationships = $this->zipEntry($template, $templateParts['drawing_relationships']);
                $restoredTargets = [
                    $generatedParts['drawing'],
                    $generatedParts['drawing_relationships'],
                ];

                if (! $generated->addFromString($generatedParts['drawing'], $templateDrawing)) {
                    throw new RuntimeException('Drawing utama template gagal dipertahankan.');
                }
                if (! $generated->addFromString($generatedParts['drawing_relationships'], $templateRelationships)) {
                    throw new RuntimeException('Relasi drawing utama template gagal dipertahankan.');
                }

                foreach ($this->internalRelationshipTargets($templateRelationships) as $target) {
                    $templateTarget = $this->resolvePartTarget($templateParts['drawing'], $target);
                    $generatedTarget = $this->resolvePartTarget($generatedParts['drawing'], $target);

                    if (! $generated->addFromString($generatedTarget, $this->zipEntry($template, $templateTarget))) {
                        throw new RuntimeException("Elemen visual template {$templateTarget} gagal dipertahankan.");
                    }
                    $restoredTargets[] = $generatedTarget;
                }
            } finally {
                $generatedClosed = $generated->close();
            }

            if (! $generatedClosed) {
                throw new RuntimeException('File surat jalan gagal ditutup setelah restorasi elemen visual.');
            }

            $this->verifyRestoredDrawing(
                $generatedPath,
                $generatedParts['drawing'],
                $templateDrawing,
                $restoredTargets
            );
        } finally {
            $template->close();
        }
    }

    /**
     * @param  array<int, string>  $expectedParts
     */
    private function verifyRestoredDrawing(
        string $generatedPath,
        string $drawingPart,
        string $expectedDrawing,
        array $expectedParts
    ): void {
        $archive = new ZipArchive;
        if ($archive->open($generatedPath) !== true) {
            throw new RuntimeException('Surat jalan hasil generasi tidak dapat dibuka kembali untuk verifikasi.');
        }

        try {
            foreach (array_unique($expectedParts) as $part) {
                $this->zipEntry($archive, $part);
            }

            if ($this->zipEntry($archive, $drawingPart) !== $expectedDrawing) {
                throw new RuntimeException('Drawing utama template tidak identik setelah restorasi.');
            }
        } finally {
            $archive->close();
        }
    }

    /**
     * @return array{drawing: string, drawing_relationships: string}
     */
    private function locateMainDrawingParts(ZipArchive $archive): array
    {
        $workbook = $this->xmlDocument($this->zipEntry($archive, 'xl/workbook.xml'));
        $workbookXPath = new DOMXPath($workbook);
        $sheetNode = null;

        foreach ($workbookXPath->query('/*[local-name()="workbook"]/*[local-name()="sheets"]/*[local-name()="sheet"]') as $node) {
            if ($node instanceof DOMElement && $node->getAttribute('name') === 'MASTER SJ UP SLA') {
                $sheetNode = $node;
                break;
            }
        }

        if (! $sheetNode instanceof DOMElement) {
            throw new RuntimeException('Sheet utama tidak ditemukan pada relasi workbook template.');
        }

        $sheetRelationshipId = $sheetNode->getAttributeNS(self::DOCUMENT_RELATIONSHIP_NAMESPACE, 'id');
        $worksheetTarget = $this->relationshipTarget(
            $this->zipEntry($archive, 'xl/_rels/workbook.xml.rels'),
            $sheetRelationshipId
        );
        $worksheetPart = $this->resolvePartTarget('xl/workbook.xml', $worksheetTarget);
        $worksheet = $this->xmlDocument($this->zipEntry($archive, $worksheetPart));
        $worksheetXPath = new DOMXPath($worksheet);
        $drawingNode = $worksheetXPath
            ->query('/*[local-name()="worksheet"]/*[local-name()="drawing"]')
            ->item(0);

        if (! $drawingNode instanceof DOMElement) {
            throw new RuntimeException('Drawing utama tidak ditemukan pada sheet utama template.');
        }

        $drawingRelationshipId = $drawingNode->getAttributeNS(self::DOCUMENT_RELATIONSHIP_NAMESPACE, 'id');
        $worksheetRelationshipsPart = $this->relationshipsPart($worksheetPart);
        $drawingTarget = $this->relationshipTarget(
            $this->zipEntry($archive, $worksheetRelationshipsPart),
            $drawingRelationshipId
        );
        $drawingPart = $this->resolvePartTarget($worksheetPart, $drawingTarget);
        $drawingRelationshipsPart = $this->relationshipsPart($drawingPart);

        $this->zipEntry($archive, $drawingPart);
        $this->zipEntry($archive, $drawingRelationshipsPart);

        return [
            'drawing' => $drawingPart,
            'drawing_relationships' => $drawingRelationshipsPart,
        ];
    }

    private function relationshipTarget(string $relationshipsXml, string $relationshipId): string
    {
        if ($relationshipId === '') {
            throw new RuntimeException('ID relasi OpenXML tidak tersedia.');
        }

        $relationships = $this->xmlDocument($relationshipsXml);

        foreach ($relationships->getElementsByTagNameNS(self::PACKAGE_RELATIONSHIP_NAMESPACE, 'Relationship') as $relationship) {
            if (! $relationship instanceof DOMElement || $relationship->getAttribute('Id') !== $relationshipId) {
                continue;
            }
            if (strcasecmp($relationship->getAttribute('TargetMode'), 'External') === 0) {
                throw new RuntimeException('Relasi drawing utama tidak boleh menunjuk target eksternal.');
            }

            return $relationship->getAttribute('Target');
        }

        throw new RuntimeException("Relasi OpenXML {$relationshipId} tidak ditemukan.");
    }

    /**
     * @return array<int, string>
     */
    private function internalRelationshipTargets(string $relationshipsXml): array
    {
        $relationships = $this->xmlDocument($relationshipsXml);
        $targets = [];

        foreach ($relationships->getElementsByTagNameNS(self::PACKAGE_RELATIONSHIP_NAMESPACE, 'Relationship') as $relationship) {
            if (
                $relationship instanceof DOMElement
                && strcasecmp($relationship->getAttribute('TargetMode'), 'External') !== 0
            ) {
                $targets[] = $relationship->getAttribute('Target');
            }
        }

        return $targets;
    }

    private function relationshipsPart(string $sourcePart): string
    {
        $directory = dirname($sourcePart);

        return ($directory === '.' ? '' : $directory.'/').'_rels/'.basename($sourcePart).'.rels';
    }

    private function resolvePartTarget(string $sourcePart, string $target): string
    {
        $target = rawurldecode(str_replace('\\', '/', trim($target)));
        if ($target === '') {
            throw new RuntimeException('Target relasi OpenXML kosong.');
        }

        $candidate = str_starts_with($target, '/')
            ? ltrim($target, '/')
            : dirname($sourcePart).'/'.$target;
        $segments = [];

        foreach (explode('/', $candidate) as $segment) {
            if ($segment === '' || $segment === '.') {
                continue;
            }
            if ($segment === '..') {
                if ($segments === []) {
                    throw new RuntimeException("Target relasi OpenXML keluar dari root paket: {$target}.");
                }
                array_pop($segments);

                continue;
            }
            $segments[] = $segment;
        }

        return implode('/', $segments);
    }

    private function zipEntry(ZipArchive $archive, string $entry): string
    {
        $contents = $archive->getFromName($entry);
        if ($contents === false) {
            throw new RuntimeException("Bagian OpenXML {$entry} tidak ditemukan.");
        }

        return $contents;
    }

    private function xmlDocument(string $xml): DOMDocument
    {
        $document = new DOMDocument;
        if (! $document->loadXML($xml, LIBXML_NONET | LIBXML_NOBLANKS)) {
            throw new RuntimeException('Struktur XML pada template surat jalan tidak valid.');
        }

        return $document;
    }

    private function documentNumber(Peminjaman $loan, $generatedAt): string
    {
        $areaCode = Str::upper(trim((string) ($loan->area?->kode ?? 'AREA')));
        $areaCode = preg_replace('/[^A-Z0-9_-]+/', '-', $areaCode) ?: 'AREA';

        return 'SJ-PENGIRIMAN/'.$areaCode.'/'.$generatedAt->format('Ym').'/'.str_pad((string) $loan->id, 6, '0', STR_PAD_LEFT);
    }

    private function formatDateTime($value): string
    {
        return $value ? $value->format('d/m/Y H:i') : '-';
    }
}
