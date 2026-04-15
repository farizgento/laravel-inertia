<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Alat;
use App\Models\Area;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;
use ZipArchive;

class AlatController extends Controller
{
    private const XLSX_MAIN_NS = 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';

    private const XLSX_REL_NS = 'http://schemas.openxmlformats.org/package/2006/relationships';

    private const CLASSIFICATION_OPTIONS = [
        'General Tools',
        'Lifting Tools',
        'Measurement Tools',
    ];

    private function isSuperAdmin(?Request $request): bool
    {
        $roleKey = strtolower((string) ($request?->user()?->role?->key ?? ''));

        return $roleKey === Role::KEY_SUPER_ADMIN;
    }

    private function getAuthorizedAreaId(Request $request): ?int
    {
        if ($this->isSuperAdmin($request)) {
            return null;
        }

        $areaId = $request->user()?->area_id;

        return $areaId ? (int) $areaId : null;
    }

    private function applyWritableArea(Request $request, array $data): array
    {
        $authorizedAreaId = $this->getAuthorizedAreaId($request);
        if ($authorizedAreaId === null) {
            return $data;
        }

        if ((int) ($data['area_id'] ?? 0) !== $authorizedAreaId) {
            throw ValidationException::withMessages([
                'area_id' => ['Anda hanya dapat mengelola alat pada area anda sendiri.'],
            ]);
        }

        $data['area_id'] = $authorizedAreaId;

        return $data;
    }

    private function ensureToolInAuthorizedArea(Request $request, Alat $alat): void
    {
        $authorizedAreaId = $this->getAuthorizedAreaId($request);
        if ($authorizedAreaId === null) {
            return;
        }

        if ((int) $alat->area_id !== $authorizedAreaId) {
            abort(403, 'Anda hanya dapat mengelola alat pada area anda sendiri.');
        }
    }

    private function formatAlat(Alat $alat, int $borrowedQty = 0): array
    {
        $stokTersedia = max(((int) $alat->total_aset) - $borrowedQty, 0);

        return [
            'id' => $alat->id,
            'kode' => sprintf('ALT-%03d', $alat->id),
            'nama' => $alat->nama,
            'jenis_alat' => $alat->jenis_alat,
            'klasifikasi_alat' => $alat->klasifikasi_alat,
            'stok' => $stokTersedia,
            'total_aset' => (int) $alat->total_aset,
            'stok_tersedia' => $stokTersedia,
            'deskripsi' => '',
            'lokasi' => $alat->area?->name ?? 'Area tidak diketahui',
            'area_name' => $alat->area?->name ?? 'Area tidak diketahui',
            'area_slug' => $alat->area?->slug,
            'area_id' => $alat->area_id,
        ];
    }

    private function borrowedMap(array $alatIds): array
    {
        if (! $alatIds) {
            return [];
        }

        return DB::table('peminjaman_items as items')
            ->join('peminjamans as pem', 'pem.id', '=', 'items.peminjaman_id')
            ->whereIn('items.alat_id', $alatIds)
            ->whereIn('pem.status', ['Menunggu Review', 'Dipesan', 'Disiapkan', 'Terkirim', 'Diterima'])
            ->groupBy('items.alat_id')
            ->select(
                'items.alat_id',
                DB::raw(
                    "SUM(CASE
                        WHEN pem.status = 'Menunggu Review' THEN items.qty
                        WHEN pem.status IN ('Dipesan', 'Disiapkan', 'Terkirim', 'Diterima') THEN COALESCE(items.approved_qty, 0)
                        ELSE 0
                    END) as total"
                )
            )
            ->pluck('total', 'alat_id')
            ->map(fn ($value) => (int) $value)
            ->all();
    }

    private function buildIndexQuery(Request $request)
    {
        $search = trim((string) $request->query('search', ''));
        $areaId = $request->query('area_id');
        $authorizedAreaId = $this->getAuthorizedAreaId($request);

        if ($request->user() && $authorizedAreaId !== null) {
            $areaId = $authorizedAreaId;
        }

        $query = Alat::query()->with('area');

        if ($search !== '') {
            $keyword = mb_strtolower($search);
            $query->where(function ($builder) use ($keyword) {
                $builder
                    ->whereRaw('LOWER(nama) LIKE ?', ['%'.$keyword.'%'])
                    ->orWhereRaw('LOWER(jenis_alat) LIKE ?', ['%'.$keyword.'%']);
            });
        }

        if (! empty($areaId)) {
            $query->where('area_id', $areaId);
        }

        return $query;
    }

    public function index(Request $request)
    {
        $perPage = (int) $request->query('per_page', 0);
        $shouldPaginate = $request->has('per_page') || $request->has('page');
        $perPageNormalized = $shouldPaginate ? ($perPage > 0 ? min($perPage, 100) : 8) : 0;
        $authorizedAreaId = $this->getAuthorizedAreaId($request);

        if ($request->user() && $authorizedAreaId !== null && ! $authorizedAreaId) {
            if ($shouldPaginate) {
                return [
                    'data' => [],
                    'meta' => [
                        'current_page' => 1,
                        'last_page' => 1,
                        'per_page' => $perPageNormalized ?: 8,
                        'total' => 0,
                    ],
                ];
            }

            return collect();
        }

        $query = $this->buildIndexQuery($request);

        if ($shouldPaginate) {
            $alats = $query->orderBy('nama')->paginate($perPageNormalized ?: 8);
            $borrowedMap = $this->borrowedMap($alats->getCollection()->pluck('id')->all());

            $data = $alats->getCollection()->map(function (Alat $alat) use ($borrowedMap) {
                $borrowedQty = $borrowedMap[$alat->id] ?? 0;

                return $this->formatAlat($alat, $borrowedQty);
            })->values();

            return [
                'data' => $data,
                'meta' => [
                    'current_page' => $alats->currentPage(),
                    'last_page' => $alats->lastPage(),
                    'per_page' => $alats->perPage(),
                    'total' => $alats->total(),
                ],
            ];
        }

        $alats = $query->orderBy('nama')->get();
        $borrowedMap = $this->borrowedMap($alats->pluck('id')->all());

        return $alats->map(function (Alat $alat) use ($borrowedMap) {
            $borrowedQty = $borrowedMap[$alat->id] ?? 0;

            return $this->formatAlat($alat, $borrowedQty);
        })->values();
    }

    public function export(Request $request): StreamedResponse
    {
        $alats = $this->buildIndexQuery($request)->orderBy('nama')->get();
        $borrowedMap = $this->borrowedMap($alats->pluck('id')->all());
        $filename = 'data-alat-'.now()->format('Ymd-His').'.csv';
        $delimiter = ';';

        return response()->streamDownload(function () use ($alats, $borrowedMap, $delimiter) {
            $handle = fopen('php://output', 'wb');
            fwrite($handle, "\xEF\xBB\xBF");
            fwrite($handle, "sep={$delimiter}\r\n");
            fputcsv($handle, ['Kode', 'Nama', 'Jenis Alat', 'Klasifikasi Alat', 'Area', 'Total Aset', 'Stok Tersedia'], $delimiter);

            foreach ($alats as $alat) {
                $formatted = $this->formatAlat($alat, $borrowedMap[$alat->id] ?? 0);

                fputcsv($handle, [
                    $formatted['kode'],
                    $formatted['nama'],
                    $formatted['jenis_alat'],
                    $formatted['klasifikasi_alat'],
                    $formatted['area_name'],
                    $formatted['total_aset'],
                    $formatted['stok_tersedia'],
                ], $delimiter);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nama' => ['required', 'string', 'max:255'],
            'jenis_alat' => ['required', 'string', 'max:255'],
            'klasifikasi_alat' => ['required', 'string', Rule::in(self::CLASSIFICATION_OPTIONS)],
            'total_aset' => ['required', 'integer', 'min:0'],
            'area_id' => ['required', 'integer', 'exists:areas,id'],
        ]);
        $data = $this->applyWritableArea($request, $data);

        $alat = Alat::create($data);
        $alat->load('area');

        $borrowedMap = $this->borrowedMap([$alat->id]);
        $borrowedQty = $borrowedMap[$alat->id] ?? 0;

        return response()->json($this->formatAlat($alat, $borrowedQty), 201);
    }

    public function update(Request $request, Alat $alat)
    {
        $this->ensureToolInAuthorizedArea($request, $alat);

        $data = $request->validate([
            'nama' => ['required', 'string', 'max:255'],
            'jenis_alat' => ['required', 'string', 'max:255'],
            'klasifikasi_alat' => ['required', 'string', Rule::in(self::CLASSIFICATION_OPTIONS)],
            'total_aset' => ['required', 'integer', 'min:0'],
            'area_id' => ['required', 'integer', 'exists:areas,id'],
        ]);
        $data = $this->applyWritableArea($request, $data);

        $alat->update($data);
        $alat->load('area');

        $borrowedMap = $this->borrowedMap([$alat->id]);
        $borrowedQty = $borrowedMap[$alat->id] ?? 0;

        return response()->json($this->formatAlat($alat, $borrowedQty));
    }

    public function import(Request $request)
    {
        $validated = $request->validate([
            'file' => [
                'required',
                'file',
                'mimes:csv,txt,xlsx',
                'max:5120',
            ],
        ]);

        $rows = $this->parseImportFile($validated['file']);
        if (! $rows) {
            throw ValidationException::withMessages([
                'file' => ['File tidak berisi data yang dapat diimpor.'],
            ]);
        }

        $areaMap = Area::query()
            ->get(['id', 'slug'])
            ->mapWithKeys(fn (Area $area) => [mb_strtolower(trim((string) $area->slug)) => $area->id])
            ->all();

        $created = 0;
        $updated = 0;
        $errors = [];
        $authorizedAreaId = $this->getAuthorizedAreaId($request);

        DB::transaction(function () use ($rows, $areaMap, $authorizedAreaId, &$created, &$updated, &$errors) {
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
                } elseif (! in_array($klasifikasiAlat, self::CLASSIFICATION_OPTIONS, true)) {
                    $rowErrors[] = 'klasifikasi alat harus General Tools, Lifting Tools, atau Measurement Tools';
                }
                if ($totalAset === null) {
                    $rowErrors[] = 'total aset harus berupa angka bulat >= 0';
                }
                if ($areaSlug === '') {
                    $rowErrors[] = 'area wajib diisi dengan slug area';
                } elseif (! array_key_exists($areaSlug, $areaMap)) {
                    $rowErrors[] = 'slug area tidak ditemukan';
                } elseif ($authorizedAreaId !== null && (int) $areaMap[$areaSlug] !== $authorizedAreaId) {
                    $rowErrors[] = 'anda hanya dapat import alat ke area anda sendiri';
                }

                if ($rowErrors) {
                    $errors[] = "Baris {$excelRow}: ".implode(', ', $rowErrors).'.';

                    continue;
                }

                $alat = Alat::updateOrCreate(
                    [
                        'nama' => $nama,
                        'jenis_alat' => $jenisAlat,
                        'klasifikasi_alat' => $klasifikasiAlat,
                        'area_id' => $areaMap[$areaSlug],
                    ],
                    [
                        'total_aset' => $totalAset,
                    ]
                );

                if ($alat->wasRecentlyCreated) {
                    $created += 1;
                } else {
                    $updated += 1;
                }
            }

            if ($errors) {
                throw ValidationException::withMessages([
                    'file' => $errors,
                ]);
            }
        });

        return response()->json([
            'message' => "Import alat berhasil. {$created} data ditambahkan, {$updated} data diperbarui.",
            'created' => $created,
            'updated' => $updated,
        ]);
    }

    public function destroy(Request $request, Alat $alat)
    {
        $this->ensureToolInAuthorizedArea($request, $alat);

        $alat->delete();

        return response()->json(['message' => 'Alat berhasil dihapus.']);
    }

    private function parseImportFile(UploadedFile $file): array
    {
        $extension = strtolower((string) $file->getClientOriginalExtension());

        return match ($extension) {
            'csv', 'txt' => $this->parseCsvFile($file),
            'xlsx' => $this->parseXlsxFile($file),
            default => throw ValidationException::withMessages([
                'file' => ['Format file tidak didukung. Gunakan CSV atau XLSX.'],
            ]),
        };
    }

    private function parseCsvFile(UploadedFile $file): array
    {
        $path = $file->getRealPath();
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

    private function parseXlsxFile(UploadedFile $file): array
    {
        if (! class_exists(ZipArchive::class)) {
            return $this->parseXlsxFileWithoutZipExtension($file);
        }

        $zip = new ZipArchive;
        if ($zip->open($file->getRealPath()) !== true) {
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

    private function parseXlsxFileWithoutZipExtension(UploadedFile $file): array
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
                $file->getRealPath(),
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
