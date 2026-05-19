<?php

namespace App\Services;

use App\Models\Alat;
use App\Models\AlatImport;
use App\Models\Area;
use App\Models\Role;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use ZipArchive;

class AlatImportService
{
    private const XLSX_MAIN_NS = 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';

    private const XLSX_REL_NS = 'http://schemas.openxmlformats.org/package/2006/relationships';

    public function downloadUrl(AlatImport $import): string
    {
        return url('/api/alats/imports/'.$import->id.'/download');
    }

    public function parseImportFile(UploadedFile|string $file): array
    {
        $extension = strtolower($file instanceof UploadedFile
            ? (string) $file->getClientOriginalExtension()
            : pathinfo($file, PATHINFO_EXTENSION));

        return match ($extension) {
            'csv', 'txt' => $this->parseCsvFile($file),
            'xlsx' => $this->parseXlsxFile($file),
            default => throw ValidationException::withMessages([
                'file' => ['Format file tidak didukung. Gunakan CSV atau XLSX.'],
            ]),
        };
    }

    public function processImport(AlatImport $import): void
    {
        $import->loadMissing('user.role', 'user.area');

        $import->update([
            'status' => AlatImport::STATUS_PROCESSING,
            'error_message' => null,
            'finished_at' => null,
            'processed_rows' => 0,
            'created_count' => 0,
            'updated_count' => 0,
        ]);

        $rows = $this->parseImportFile(storage_path('app/'.$import->file_path));
        $import->update([
            'total_rows' => count($rows),
        ]);

        $areaMap = Area::query()
            ->get(['id', 'name', 'slug', 'kode'])
            ->flatMap(function (Area $area) {
                return collect([$area->slug, $area->kode, $area->name])
                    ->filter(fn ($value) => trim((string) $value) !== '')
                    ->mapWithKeys(fn ($value) => [mb_strtolower(trim((string) $value)) => $area->id]);
            })
            ->all();

        $user = $import->user()->with('role')->first();
        $authorizedAreaId = $this->getAuthorizedAreaId($user);

        $created = 0;
        $updated = 0;
        $processed = 0;
        $errors = [];

        DB::transaction(function () use (
            $import,
            $rows,
            $areaMap,
            $authorizedAreaId,
            &$created,
            &$updated,
            &$processed,
            &$errors
        ) {
            foreach ($rows as $row) {
                $cells = $row['cells'];
                $excelRow = $row['row_number'];

                if ($this->isHeadingRow($cells)) {
                    continue;
                }

                $nama = $this->normalizeImportedText($cells[0] ?? null);
                $jenisAlat = $this->normalizeImportedText($cells[1] ?? null);
                $klasifikasiAlat = $this->normalizeImportedText($cells[2] ?? null);
                $totalAset = $this->normalizeImportedNumber($cells[3] ?? null);
                $areaSlug = mb_strtolower($this->normalizeImportedText($cells[4] ?? null));

                if ($nama === '' && $jenisAlat === '' && $klasifikasiAlat === '' && $totalAset === null && $areaSlug === '') {
                    continue;
                }

                $rowErrors = [];

                if ($nama === '') {
                    $rowErrors[] = 'nama alat wajib diisi';
                }
                if ($jenisAlat === '') {
                    $rowErrors[] = 'jenis alat wajib diisi';
                }
                if ($klasifikasiAlat === '') {
                    $rowErrors[] = 'klasifikasi alat wajib diisi';
                } elseif (mb_strlen($klasifikasiAlat) > 255) {
                    $rowErrors[] = 'klasifikasi alat maksimal 255 karakter';
                }
                if ($totalAset === null) {
                    $rowErrors[] = 'total aset harus berupa angka bulat >= 0';
                }
                if ($areaSlug === '') {
                    $rowErrors[] = 'area wajib diisi dengan kode atau slug area';
                } elseif (! array_key_exists($areaSlug, $areaMap)) {
                    $rowErrors[] = 'kode atau slug area tidak ditemukan';
                } elseif ($authorizedAreaId !== null && (int) $areaMap[$areaSlug] !== $authorizedAreaId) {
                    $rowErrors[] = 'anda hanya dapat import alat ke area anda sendiri';
                }

                if ($rowErrors) {
                    $errors[] = "Baris {$excelRow}: ".implode(', ', $rowErrors).'.';

                    continue;
                }

                $lookup = [
                    'nama' => $nama,
                    'jenis_alat' => $jenisAlat,
                    'klasifikasi_alat' => $klasifikasiAlat,
                    'area_id' => $areaMap[$areaSlug],
                ];
                $alat = Alat::query()->where($lookup)->first();
                $oldValues = $alat?->getAttributes() ?? [];

                $alat = Alat::withoutEvents(function () use ($alat, $lookup, $totalAset) {
                    if ($alat) {
                        $alat->update([
                            'total_aset' => $totalAset,
                        ]);

                        return $alat->refresh();
                    }

                    return Alat::create([
                        ...$lookup,
                        'total_aset' => $totalAset,
                    ]);
                });

                if ($alat->wasRecentlyCreated) {
                    $created += 1;
                    $this->logImportedAlatActivity('create', $alat, [], [
                        'nama' => $alat->nama,
                        'jenis_alat' => $alat->jenis_alat,
                        'klasifikasi_alat' => $alat->klasifikasi_alat,
                        'total_aset' => (int) $alat->total_aset,
                        'area_id' => (int) $alat->area_id,
                    ], $import);
                } else {
                    $updated += 1;
                    if ((int) ($oldValues['total_aset'] ?? 0) !== (int) $totalAset) {
                        $this->logImportedAlatActivity('update', $alat, [
                            'total_aset' => (int) ($oldValues['total_aset'] ?? 0),
                        ], [
                            'total_aset' => (int) $totalAset,
                        ], $import);
                    }
                }

                $processed += 1;

                if ($processed % 25 === 0) {
                    $import->forceFill([
                        'processed_rows' => $processed,
                        'created_count' => $created,
                        'updated_count' => $updated,
                    ])->save();
                }
            }

            if ($errors) {
                throw ValidationException::withMessages([
                    'file' => $errors,
                ]);
            }
        });

        $import->update([
            'status' => AlatImport::STATUS_COMPLETED,
            'processed_rows' => $processed,
            'created_count' => $created,
            'updated_count' => $updated,
            'finished_at' => now(),
        ]);

        $this->logImportActivity($import->fresh(['user.role', 'user.area']));
    }

    public function markAsFailed(AlatImport $import, string $message): void
    {
        $import->update([
            'status' => AlatImport::STATUS_FAILED,
            'error_message' => $message,
            'finished_at' => now(),
        ]);

        $this->logImportActivity($import->fresh(['user.role', 'user.area']));
    }

    public function formatImport(AlatImport $import): array
    {
        $status = $this->normalizeStatus($import->status);

        return [
            'id' => $import->id,
            'file_name' => $import->file_name,
            'status' => $status,
            'raw_status' => $import->status,
            'total_rows' => (int) $import->total_rows,
            'processed_rows' => (int) $import->processed_rows,
            'created_count' => (int) $import->created_count,
            'updated_count' => (int) $import->updated_count,
            'error_message' => $import->error_message,
            'finished_at' => $import->finished_at?->toISOString(),
            'created_at' => $import->created_at?->toISOString(),
            'download_url' => $this->downloadUrl($import),
            'is_finished' => in_array($status, [AlatImport::STATUS_COMPLETED, AlatImport::STATUS_FAILED], true),
        ];
    }

    private function normalizeStatus(?string $status): string
    {
        $normalized = strtolower((string) $status);

        if (in_array($normalized, ['done', 'complete', 'success', 'succeeded'], true)) {
            return AlatImport::STATUS_COMPLETED;
        }

        if (in_array($normalized, ['fail', 'error', 'errored'], true)) {
            return AlatImport::STATUS_FAILED;
        }

        return in_array($normalized, [
            AlatImport::STATUS_PENDING,
            AlatImport::STATUS_PROCESSING,
            AlatImport::STATUS_COMPLETED,
            AlatImport::STATUS_FAILED,
        ], true)
            ? $normalized
            : AlatImport::STATUS_PENDING;
    }

    public function ensureImportAccessible(AlatImport $import, User $user): void
    {
        $roleKey = strtolower((string) ($user->role?->key ?? ''));
        if ($roleKey === Role::KEY_SUPER_ADMIN) {
            return;
        }

        abort_unless((int) $import->user_id === (int) $user->id, 403, 'Anda tidak memiliki akses ke import ini.');
    }

    private function getAuthorizedAreaId(?User $user): ?int
    {
        $roleKey = strtolower((string) ($user?->role?->key ?? ''));
        if ($roleKey === Role::KEY_SUPER_ADMIN) {
            return null;
        }

        $areaId = $user?->area_id;

        return $areaId ? (int) $areaId : null;
    }

    private function logImportActivity(AlatImport $import): void
    {
        $actor = $import->user;
        $downloadUrl = $this->downloadUrl($import);
        $baseValues = [
            'file_name' => $import->file_name,
            'file_download_url' => $downloadUrl,
        ];

        ActivityLogger::log('import', $import, [
            'actor' => $actor,
            'area_id' => $actor?->area_id,
            'subject_type' => 'Import Alat',
            'subject_label' => $import->file_name,
            'description' => $this->buildImportDescription($import, $actor),
            'method' => 'QUEUE',
            'route' => 'api/alats/import',
            'url' => $downloadUrl,
            'old_values' => [
                ...$baseValues,
                'status' => AlatImport::STATUS_PENDING,
            ],
            'new_values' => [
                ...$baseValues,
                'status' => $import->status,
                'total_rows' => (int) $import->total_rows,
                'processed_rows' => (int) $import->processed_rows,
                'created_count' => (int) $import->created_count,
                'updated_count' => (int) $import->updated_count,
                'error_message' => $import->error_message,
            ],
            'properties' => [
                'import_id' => $import->id,
                'finished_at' => $import->finished_at?->format('Y-m-d H:i:s'),
            ],
        ]);
    }

    private function logImportedAlatActivity(string $action, Alat $alat, array $oldValues, array $newValues, AlatImport $import): void
    {
        $actor = $import->user;
        $actionLabel = $action === 'create' ? 'menambahkan' : 'memperbarui';

        ActivityLogger::log($action, $alat, [
            'actor' => $actor,
            'area_id' => $alat->area_id,
            'subject_type' => 'Alat',
            'subject_label' => $alat->nama,
            'description' => trim(($actor?->name ?? 'System') . " {$actionLabel} Alat {$alat->nama} melalui import {$import->file_name}"),
            'method' => 'QUEUE',
            'route' => 'api/alats/import',
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'properties' => [
                'import_id' => $import->id,
                'file_name' => $import->file_name,
            ],
        ]);
    }

    private function buildImportDescription(AlatImport $import, ?User $actor): string
    {
        $actorName = $actor?->name ?? 'System';

        return match ($import->status) {
            AlatImport::STATUS_COMPLETED => "{$actorName} menyelesaikan import alat {$import->file_name}.",
            AlatImport::STATUS_FAILED => "{$actorName} gagal import alat {$import->file_name}.",
            default => "{$actorName} memproses import alat {$import->file_name}.",
        };
    }

    private function parseCsvFile(UploadedFile|string $file): array
    {
        $path = $file instanceof UploadedFile ? $file->getRealPath() : $file;
        $handle = fopen($path, 'rb');

        if (! $handle) {
            throw ValidationException::withMessages([
                'file' => ['File CSV tidak dapat dibaca.'],
            ]);
        }

        $delimiter = $this->detectCsvDelimiter((string) file_get_contents($path, false, null, 0, 2048));
        $rows = [];
        $rowNumber = 0;

        while (($data = fgetcsv($handle, 0, $delimiter)) !== false) {
            $rowNumber += 1;
            $cells = array_map(
                fn ($value) => is_string($value) ? trim(preg_replace('/^\xEF\xBB\xBF/', '', $value)) : '',
                $data
            );

            if ($this->rowHasValues($cells)) {
                $rows[] = [
                    'row_number' => $rowNumber,
                    'cells' => $cells,
                ];
            }
        }

        fclose($handle);

        return $rows;
    }

    private function parseXlsxFile(UploadedFile|string $file): array
    {
        $path = $file instanceof UploadedFile ? $file->getRealPath() : $file;

        if (! class_exists(ZipArchive::class)) {
            return $this->parseXlsxFileWithoutZipExtension($path);
        }

        $zip = new ZipArchive;
        if ($zip->open($path) !== true) {
            throw ValidationException::withMessages([
                'file' => ['File XLSX tidak dapat dibuka.'],
            ]);
        }

        $workbookXml = $zip->getFromName('xl/workbook.xml');
        $relsXml = $zip->getFromName('xl/_rels/workbook.xml.rels');
        if (! $workbookXml || ! $relsXml) {
            $zip->close();
            throw ValidationException::withMessages([
                'file' => ['Struktur file XLSX tidak valid.'],
            ]);
        }

        $sheetPath = $this->resolveFirstWorksheetPath($workbookXml, $relsXml);
        $sheetXml = $zip->getFromName($sheetPath);
        $sharedStringsXml = $zip->getFromName('xl/sharedStrings.xml');
        $zip->close();

        if (! $sheetXml) {
            throw ValidationException::withMessages([
                'file' => ['Sheet pertama pada file XLSX tidak ditemukan.'],
            ]);
        }

        return $this->parseXlsxContents($sheetXml, $sharedStringsXml ?: '');
    }

    private function parseXlsxFileWithoutZipExtension(string $path): array
    {
        if (PHP_OS_FAMILY !== 'Windows') {
            throw ValidationException::withMessages([
                'file' => ['Ekstensi PHP ZipArchive tidak tersedia, sehingga file XLSX belum bisa dibaca.'],
            ]);
        }

        $tempDirectory = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR)
            .DIRECTORY_SEPARATOR
            .'alat-import-'
            .Str::uuid();

        File::ensureDirectoryExists($tempDirectory);

        try {
            $result = Process::timeout(20)->run([
                'powershell',
                '-NoProfile',
                '-NonInteractive',
                '-Command',
                'Expand-Archive -LiteralPath $args[0] -DestinationPath $args[1] -Force',
                $path,
                $tempDirectory,
            ]);

            if ($result->failed()) {
                throw ValidationException::withMessages([
                    'file' => ['File XLSX tidak dapat diekstrak untuk diproses.'],
                ]);
            }

            $workbookPath = $tempDirectory.DIRECTORY_SEPARATOR.'xl'.DIRECTORY_SEPARATOR.'workbook.xml';
            $relsPath = $tempDirectory.DIRECTORY_SEPARATOR.'xl'.DIRECTORY_SEPARATOR.'_rels'.DIRECTORY_SEPARATOR.'workbook.xml.rels';

            if (! File::exists($workbookPath) || ! File::exists($relsPath)) {
                throw ValidationException::withMessages([
                    'file' => ['Struktur file XLSX tidak valid.'],
                ]);
            }

            $sheetPath = $this->resolveFirstWorksheetPath(
                (string) File::get($workbookPath),
                (string) File::get($relsPath)
            );

            $normalizedSheetPath = str_replace('/', DIRECTORY_SEPARATOR, $sheetPath);
            $sheetFullPath = $tempDirectory.DIRECTORY_SEPARATOR.$normalizedSheetPath;
            $sharedStringsPath = $tempDirectory.DIRECTORY_SEPARATOR.'xl'.DIRECTORY_SEPARATOR.'sharedStrings.xml';

            if (! File::exists($sheetFullPath)) {
                throw ValidationException::withMessages([
                    'file' => ['Sheet pertama pada file XLSX tidak ditemukan.'],
                ]);
            }

            return $this->parseXlsxContents(
                (string) File::get($sheetFullPath),
                File::exists($sharedStringsPath) ? (string) File::get($sharedStringsPath) : ''
            );
        } finally {
            File::deleteDirectory($tempDirectory);
        }
    }

    private function parseXlsxContents(string $sheetXml, string $sharedStringsXml = ''): array
    {
        $sharedStrings = $this->parseSharedStrings($sharedStringsXml);
        $sheet = simplexml_load_string($sheetXml);
        if (! $sheet) {
            throw ValidationException::withMessages([
                'file' => ['Sheet XLSX tidak dapat diproses.'],
            ]);
        }

        $sheet->registerXPathNamespace('main', self::XLSX_MAIN_NS);

        $rows = [];
        foreach ($sheet->xpath('//main:sheetData/main:row') ?: [] as $row) {
            $rowNumber = (int) ($row['r'] ?? 0);
            $cells = [];
            $row->registerXPathNamespace('main', self::XLSX_MAIN_NS);

            foreach ($row->xpath('./main:c') ?: [] as $cell) {
                $reference = (string) ($cell['r'] ?? '');
                $columnIndex = $this->columnReferenceToIndex($reference);
                if ($columnIndex < 0) {
                    continue;
                }

                $cells[$columnIndex] = $this->extractSpreadsheetCellValue($cell, $sharedStrings);
            }

            if (! $cells) {
                continue;
            }

            ksort($cells);
            $normalizedCells = [];
            $maxIndex = max(array_keys($cells));
            for ($index = 0; $index <= $maxIndex; $index += 1) {
                $normalizedCells[$index] = trim((string) ($cells[$index] ?? ''));
            }

            if ($this->rowHasValues($normalizedCells)) {
                $rows[] = [
                    'row_number' => $rowNumber > 0 ? $rowNumber : count($rows) + 1,
                    'cells' => $normalizedCells,
                ];
            }
        }

        return $rows;
    }

    private function resolveFirstWorksheetPath(string $workbookXml, string $relsXml): string
    {
        $workbook = simplexml_load_string($workbookXml);
        $rels = simplexml_load_string($relsXml);

        if (! $workbook || ! $rels) {
            throw ValidationException::withMessages([
                'file' => ['Metadata file XLSX tidak dapat dibaca.'],
            ]);
        }

        $workbook->registerXPathNamespace('main', self::XLSX_MAIN_NS);
        $rels->registerXPathNamespace('rel', self::XLSX_REL_NS);

        $relationshipId = null;
        foreach ($workbook->xpath('//main:sheets/main:sheet') ?: [] as $sheet) {
            $attributes = $sheet->attributes('http://schemas.openxmlformats.org/officeDocument/2006/relationships');
            $relationshipId = (string) ($attributes['id'] ?? '');
            if ($relationshipId !== '') {
                break;
            }
        }

        if ($relationshipId === null || $relationshipId === '') {
            throw ValidationException::withMessages([
                'file' => ['Sheet pertama pada file XLSX tidak ditemukan.'],
            ]);
        }

        foreach ($rels->xpath('//rel:Relationship') ?: [] as $relationship) {
            $attributes = $relationship->attributes();
            if ((string) ($attributes['Id'] ?? '') !== $relationshipId) {
                continue;
            }

            $target = str_replace(['\\', '../'], ['/', ''], (string) ($attributes['Target'] ?? ''));

            return Str::startsWith($target, 'xl/')
                ? $target
                : 'xl/'.ltrim($target, '/');
        }

        throw ValidationException::withMessages([
            'file' => ['Relasi sheet XLSX tidak ditemukan.'],
        ]);
    }

    private function parseSharedStrings(string $xml): array
    {
        if ($xml === '') {
            return [];
        }

        $shared = simplexml_load_string($xml);
        if (! $shared) {
            return [];
        }

        $shared->registerXPathNamespace('main', self::XLSX_MAIN_NS);

        $values = [];
        foreach ($shared->xpath('//main:si') ?: [] as $item) {
            $itemChildren = $item->children(self::XLSX_MAIN_NS);

            if (isset($itemChildren->t)) {
                $values[] = (string) $itemChildren->t;

                continue;
            }

            $text = '';
            foreach ($itemChildren->r ?? [] as $run) {
                $runChildren = $run->children(self::XLSX_MAIN_NS);
                $text .= isset($runChildren->t) ? (string) $runChildren->t : '';
            }
            $values[] = $text;
        }

        return $values;
    }

    private function extractSpreadsheetCellValue(\SimpleXMLElement $cell, array $sharedStrings): string
    {
        $type = (string) ($cell['t'] ?? '');
        $cellChildren = $cell->children(self::XLSX_MAIN_NS);

        if ($type === 'inlineStr') {
            $inlineString = $cellChildren->is ?? null;
            $inlineChildren = $inlineString ? $inlineString->children(self::XLSX_MAIN_NS) : null;

            return $inlineChildren && isset($inlineChildren->t) ? (string) $inlineChildren->t : '';
        }

        $value = isset($cellChildren->v) ? (string) $cellChildren->v : '';
        if ($type === 's') {
            return (string) ($sharedStrings[(int) $value] ?? '');
        }

        return $value;
    }

    private function columnReferenceToIndex(string $reference): int
    {
        if (! preg_match('/^[A-Z]+/i', $reference, $matches)) {
            return -1;
        }

        $column = strtoupper($matches[0]);
        $index = 0;

        foreach (str_split($column) as $letter) {
            $index = ($index * 26) + (ord($letter) - 64);
        }

        return $index - 1;
    }

    private function detectCsvDelimiter(string $sample): string
    {
        $delimiters = [',', ';', "\t", '|'];
        $bestDelimiter = ',';
        $bestScore = -1;

        foreach ($delimiters as $delimiter) {
            $score = count(str_getcsv($sample, $delimiter));
            if ($score > $bestScore) {
                $bestScore = $score;
                $bestDelimiter = $delimiter;
            }
        }

        return $bestDelimiter;
    }

    private function rowHasValues(array $cells): bool
    {
        foreach ($cells as $cell) {
            if (trim((string) $cell) !== '') {
                return true;
            }
        }

        return false;
    }

    private function isHeadingRow(array $cells): bool
    {
        $normalized = array_map(function ($value) {
            return Str::of((string) $value)
                ->lower()
                ->ascii()
                ->replace([' ', '_', '-'], '')
                ->value();
        }, array_slice($cells, 0, 5));

        return in_array($normalized[0] ?? '', ['nama', 'namaalat'], true)
            && in_array($normalized[1] ?? '', ['jenis', 'jenisalat'], true)
            && in_array($normalized[2] ?? '', ['klasifikasi', 'klasifikasialat'], true);
    }

    private function normalizeImportedText(mixed $value): string
    {
        return trim((string) ($value ?? ''));
    }

    private function normalizeImportedNumber(mixed $value): ?int
    {
        $normalized = trim((string) ($value ?? ''));
        if ($normalized === '') {
            return null;
        }

        $normalized = str_replace(' ', '', $normalized);

        if (preg_match('/^\d{1,3}([.,]\d{3})+$/', $normalized)) {
            $normalized = str_replace([',', '.'], '', $normalized);
        } elseif (preg_match('/^\d+[.,]0+$/', $normalized)) {
            $normalized = preg_replace('/[.,]0+$/', '', $normalized);
        } elseif (! preg_match('/^\d+$/', $normalized)) {
            return null;
        }

        $number = (int) $normalized;

        return $number >= 0 ? $number : null;
    }
}
