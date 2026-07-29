<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\ImportAlatJob;
use App\Models\Alat;
use App\Models\AlatImport;
use App\Models\AreaAlatStock;
use App\Models\Peminjaman;
use App\Models\Role;
use App\Services\AlatImportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AlatController extends Controller
{
    private function isSuperAdmin(?Request $request): bool
    {
        $roleKey = strtolower((string) ($request?->user()?->role?->key ?? ''));

        return $roleKey === Role::KEY_SUPER_ADMIN;
    }

    private function isAreaSwitcherReader(?Request $request): bool
    {
        $roleKey = strtolower((string) ($request?->user()?->role?->key ?? ''));

        return in_array($roleKey, [Role::KEY_GUEST, Role::KEY_SUPER_ADMIN], true);
    }

    private function isMgrTool(?Request $request): bool
    {
        $roleKey = strtolower((string) ($request?->user()?->role?->key ?? ''));

        return $roleKey === Role::KEY_MGR_TOOL;
    }

    private function isGuest(?Request $request): bool
    {
        $roleKey = strtolower((string) ($request?->user()?->role?->key ?? ''));

        return $roleKey === Role::KEY_GUEST;
    }

    private function isUserAreaBrowser(Request $request): bool
    {
        $roleKey = strtolower((string) ($request->user()?->role?->key ?? ''));

        return $roleKey === Role::KEY_USER
            && $request->boolean('browse_area')
            && $request->filled('area_id');
    }

    private function getAuthorizedAreaId(Request $request): ?int
    {
        if ($this->isAreaSwitcherReader($request)) {
            return null;
        }

        $areaId = $request->user()?->area_id;

        return $areaId ? (int) $areaId : null;
    }

    private function canBrowseInterAreaSource(Request $request): bool
    {
        $roleKey = strtolower((string) ($request->user()?->role?->key ?? ''));

        return in_array($roleKey, [Role::KEY_PIC_TOOL, Role::KEY_SUPER_ADMIN], true)
            && $request->boolean('inter_area_source');
    }

    private function resolveReadableAreaId(Request $request): ?int
    {
        $requestedAreaId = $request->filled('area_id')
            ? (int) $request->query('area_id')
            : null;

        if ($request->user() && $this->isGuest($request) && $requestedAreaId === null) {
            $userAreaId = $request->user()?->area_id;

            return $userAreaId ? (int) $userAreaId : null;
        }

        if ($request->user() && ($this->isAreaSwitcherReader($request) || $this->isMgrTool($request))) {
            return $requestedAreaId;
        }

        $authorizedAreaId = $this->getAuthorizedAreaId($request);
        if ($request->user()
            && $authorizedAreaId !== null
            && ! $this->canBrowseInterAreaSource($request)
            && ! $this->isUserAreaBrowser($request)
        ) {
            return $authorizedAreaId;
        }

        return $requestedAreaId;
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

    private function formatAlat(Alat $alat, int $borrowedQty = 0, ?int $totalOverride = null, array $extra = []): array
    {
        $totalAset = $totalOverride ?? (int) $alat->total_aset;
        $stokTersedia = max($totalAset - $borrowedQty, 0);

        return [
            'id' => $alat->id,
            'kode' => $alat->kode,
            'nama' => $alat->nama,
            'jenis_alat' => $alat->jenis_alat,
            'klasifikasi_alat' => $alat->klasifikasi_alat,
            'stok' => $stokTersedia,
            'total_aset' => $totalAset,
            'stok_tersedia' => $stokTersedia,
            'deskripsi' => '',
            'lokasi' => $alat->area?->name ?? 'Area tidak diketahui',
            'area_name' => $alat->area?->name ?? 'Area tidak diketahui',
            'area_slug' => $alat->area?->slug,
            'area_kode' => $alat->area?->kode,
            'area_id' => $alat->area_id,
        ] + $extra;
    }

    private function borrowedMap(array $alatIds): array
    {
        if (! $alatIds) {
            return [];
        }

        return DB::table('peminjaman_items as items')
            ->join('peminjamans as pem', 'pem.id', '=', 'items.peminjaman_id')
            ->whereIn('items.alat_id', $alatIds)
            ->whereIn('pem.status', Peminjaman::stockHoldingStatuses())
            ->groupBy('items.alat_id')
            ->select(
                'items.alat_id',
                DB::raw(
                    "SUM(CASE
                        WHEN pem.status IN ('".Peminjaman::STATUS_PERLU_DISETUJUI."', '".Peminjaman::STATUS_PERLU_DIREVIEW."') THEN items.qty
                        WHEN pem.status IN ('".Peminjaman::STATUS_DISETUJUI."', '".Peminjaman::STATUS_DIKIRIM."') THEN COALESCE(items.approved_qty, 0)
                        WHEN pem.status IN ('".Peminjaman::STATUS_DITERIMA."', '".Peminjaman::STATUS_DIKEMBALIKAN_PARTIALS."') THEN GREATEST(COALESCE(items.approved_qty, 0) - COALESCE(items.returned_qty, 0), 0)
                        ELSE 0
                    END) as total"
                )
            )
            ->pluck('total', 'alat_id')
            ->map(fn ($value) => (int) $value)
            ->all();
    }

    private function borrowedMapForArea(array $alatIds, int $areaId): array
    {
        if (! $alatIds) {
            return [];
        }

        return DB::table('peminjaman_items as items')
            ->join('peminjamans as pem', 'pem.id', '=', 'items.peminjaman_id')
            ->whereIn('items.alat_id', $alatIds)
            ->where('pem.area_id', $areaId)
            ->whereIn('pem.status', Peminjaman::stockHoldingStatuses())
            ->groupBy('items.alat_id')
            ->select(
                'items.alat_id',
                DB::raw(
                    "SUM(CASE
                        WHEN pem.status IN ('".Peminjaman::STATUS_PERLU_DISETUJUI."', '".Peminjaman::STATUS_PERLU_DIREVIEW."') THEN items.qty
                        WHEN pem.status IN ('".Peminjaman::STATUS_DISETUJUI."', '".Peminjaman::STATUS_DIKIRIM."') THEN COALESCE(items.approved_qty, 0)
                        WHEN pem.status IN ('".Peminjaman::STATUS_DITERIMA."', '".Peminjaman::STATUS_DIKEMBALIKAN_PARTIALS."') THEN GREATEST(COALESCE(items.approved_qty, 0) - COALESCE(items.returned_qty, 0), 0)
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
        $classification = trim((string) ($request->query('klasifikasi_alat', $request->query('classification', ''))));
        $areaId = $this->resolveReadableAreaId($request);

        $query = Alat::query()->with('area:id,name,slug,kode');

        if ($search !== '') {
            $keyword = mb_strtolower($search);
            $query->where(function ($builder) use ($keyword) {
                $builder
                    ->whereRaw('LOWER(nama) LIKE ?', ['%'.$keyword.'%'])
                    ->orWhereRaw('LOWER(jenis_alat) LIKE ?', ['%'.$keyword.'%'])
                    ->orWhereHas('area', function ($areaQuery) use ($keyword) {
                        $areaQuery->whereRaw('LOWER(kode) LIKE ?', ['%'.$keyword.'%']);
                    });
            });
        }

        if ($classification !== '') {
            $query->where('klasifikasi_alat', $classification);
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
        $areaId = $this->resolveReadableAreaId($request);

        if (! empty($areaId) && ! $this->canBrowseInterAreaSource($request)) {
            return $this->indexForArea((int) $areaId, $query, $request, $shouldPaginate, $perPageNormalized);
        }

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

    private function indexForArea(int $areaId, $ownedQuery, Request $request, bool $shouldPaginate, int $perPageNormalized)
    {
        if ($shouldPaginate) {
            return $this->paginatedIndexForArea($areaId, $ownedQuery, $request, $perPageNormalized);
        }

        $ownedAlats = $ownedQuery->orderBy('nama')->get();
        $sharedStocks = AreaAlatStock::query()
            ->with([
                'alat:id,nama,jenis_alat,klasifikasi_alat,total_aset,area_id',
                'alat.area:id,name,slug,kode',
                'sourcePeminjaman:id,tanggal_pinjam,tanggal_kembali',
            ])
            ->where('area_id', $areaId)
            ->where('active', true)
            ->where('qty', '>', 0)
            ->whereHas('alat', function ($query) use ($areaId, $request) {
                $query->where('area_id', '!=', $areaId);
                $search = trim((string) $request->query('search', ''));
                $classification = trim((string) ($request->query('klasifikasi_alat', $request->query('classification', ''))));
                if ($search !== '') {
                    $keyword = mb_strtolower($search);
                    $query->where(function ($builder) use ($keyword) {
                        $builder
                            ->whereRaw('LOWER(nama) LIKE ?', ['%'.$keyword.'%'])
                            ->orWhereRaw('LOWER(jenis_alat) LIKE ?', ['%'.$keyword.'%'])
                            ->orWhereHas('area', function ($areaQuery) use ($keyword) {
                                $areaQuery->whereRaw('LOWER(kode) LIKE ?', ['%'.$keyword.'%']);
                            });
                    });
                }
                if ($classification !== '') {
                    $query->where('klasifikasi_alat', $classification);
                }
            })
            ->get();

        $alatIds = $ownedAlats
            ->pluck('id')
            ->merge($sharedStocks->pluck('alat_id'))
            ->unique()
            ->values()
            ->all();
        $borrowedMap = $this->borrowedMapForArea($alatIds, $areaId);

        $rows = $ownedAlats->toBase()->map(function (Alat $alat) use ($borrowedMap) {
            return $this->formatAlat($alat, $borrowedMap[$alat->id] ?? 0, null, [
                'is_shared_area_stock' => false,
            ]);
        });

        $sharedRows = $sharedStocks
            ->toBase()
            ->filter(fn (AreaAlatStock $stock) => $stock->alat)
            ->map(function (AreaAlatStock $stock) use ($borrowedMap) {
                $alat = $stock->alat;

                return $this->formatAlat($alat, $borrowedMap[$alat->id] ?? 0, (int) $stock->qty, [
                    'is_shared_area_stock' => true,
                    'source_area_name' => $alat->area?->name ?? 'Area tidak diketahui',
                    'source_peminjaman_id' => $stock->source_peminjaman_id,
                    'source_borrow_date' => $stock->sourcePeminjaman?->tanggal_pinjam?->toDateString(),
                    'source_return_date' => $stock->sourcePeminjaman?->tanggal_kembali?->toDateString(),
                ]);
            });

        $data = $rows
            ->merge($sharedRows)
            ->sortBy('nama')
            ->values();

        return $data;
    }

    private function paginatedIndexForArea(int $areaId, $ownedQuery, Request $request, int $perPageNormalized): array
    {
        $perPage = $perPageNormalized ?: 8;
        $currentPage = max((int) $request->query('page', 1), 1);
        $total = (int) DB::query()
            ->fromSub($this->buildAreaIndexRowsQuery($areaId, $ownedQuery, $request), 'alat_index_rows')
            ->count();
        $pageRows = DB::query()
            ->fromSub($this->buildAreaIndexRowsQuery($areaId, $ownedQuery, $request), 'alat_index_rows')
            ->orderBy('sort_nama')
            ->orderBy('is_shared_area_stock')
            ->orderBy('alat_id')
            ->orderBy('stock_id')
            ->offset(($currentPage - 1) * $perPage)
            ->limit($perPage)
            ->get();

        $ownedIds = $pageRows
            ->filter(fn ($row) => (int) $row->is_shared_area_stock === 0)
            ->pluck('alat_id')
            ->map(fn ($id) => (int) $id)
            ->all();
        $sharedStockIds = $pageRows
            ->filter(fn ($row) => (int) $row->is_shared_area_stock === 1 && $row->stock_id !== null)
            ->pluck('stock_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $ownedAlats = $ownedIds
            ? Alat::query()
                ->with('area:id,name,slug,kode')
                ->whereIn('id', $ownedIds)
                ->get()
                ->keyBy('id')
            : collect();
        $sharedStocks = $sharedStockIds
            ? AreaAlatStock::query()
                ->with([
                    'alat:id,nama,jenis_alat,klasifikasi_alat,total_aset,area_id',
                    'alat.area:id,name,slug,kode',
                    'sourcePeminjaman:id,tanggal_pinjam,tanggal_kembali',
                ])
                ->whereIn('id', $sharedStockIds)
                ->get()
                ->keyBy('id')
            : collect();
        $alatIds = $pageRows
            ->pluck('alat_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
        $borrowedMap = $this->borrowedMapForArea($alatIds, $areaId);
        $pageData = $pageRows
            ->map(function ($row) use ($ownedAlats, $sharedStocks, $borrowedMap) {
                if ((int) $row->is_shared_area_stock === 0) {
                    $alat = $ownedAlats->get((int) $row->alat_id);

                    return $alat
                        ? $this->formatAlat($alat, $borrowedMap[$alat->id] ?? 0, null, [
                            'is_shared_area_stock' => false,
                        ])
                        : null;
                }

                $stock = $sharedStocks->get((int) $row->stock_id);
                $alat = $stock?->alat;
                if (! $stock || ! $alat) {
                    return null;
                }

                return $this->formatAlat($alat, $borrowedMap[$alat->id] ?? 0, (int) $stock->qty, [
                    'is_shared_area_stock' => true,
                    'source_area_name' => $alat->area?->name ?? 'Area tidak diketahui',
                    'source_peminjaman_id' => $stock->source_peminjaman_id,
                    'source_borrow_date' => $stock->sourcePeminjaman?->tanggal_pinjam?->toDateString(),
                    'source_return_date' => $stock->sourcePeminjaman?->tanggal_kembali?->toDateString(),
                ]);
            })
            ->filter()
            ->values();

        return [
            'data' => $pageData,
            'meta' => [
                'current_page' => $currentPage,
                'last_page' => max((int) ceil($total / $perPage), 1),
                'per_page' => $perPage,
                'total' => $total,
            ],
        ];
    }

    private function buildAreaIndexRowsQuery(int $areaId, $ownedQuery, Request $request)
    {
        $ownedRows = (clone $ownedQuery)
            ->reorder()
            ->select([
                'alats.id as alat_id',
                DB::raw('alats.id - alats.id as stock_id'),
                DB::raw('0 as is_shared_area_stock'),
                'alats.nama as sort_nama',
            ])
            ->toBase();

        $sharedRows = $this->buildSharedAreaStockRowsQuery($areaId, $request);

        return $ownedRows->unionAll($sharedRows);
    }

    private function buildSharedAreaStockRowsQuery(int $areaId, Request $request)
    {
        $search = trim((string) $request->query('search', ''));
        $classification = trim((string) ($request->query('klasifikasi_alat', $request->query('classification', ''))));

        $query = DB::table('area_alat_stocks')
            ->join('alats', 'alats.id', '=', 'area_alat_stocks.alat_id')
            ->leftJoin('areas', 'areas.id', '=', 'alats.area_id')
            ->where('area_alat_stocks.area_id', $areaId)
            ->where('area_alat_stocks.active', true)
            ->where('area_alat_stocks.qty', '>', 0)
            ->where('alats.area_id', '!=', $areaId);

        if ($search !== '') {
            $keyword = mb_strtolower($search);
            $query->where(function ($builder) use ($keyword) {
                $builder
                    ->whereRaw('LOWER(alats.nama) LIKE ?', ['%'.$keyword.'%'])
                    ->orWhereRaw('LOWER(alats.jenis_alat) LIKE ?', ['%'.$keyword.'%'])
                    ->orWhereRaw('LOWER(areas.kode) LIKE ?', ['%'.$keyword.'%']);
            });
        }

        if ($classification !== '') {
            $query->where('alats.klasifikasi_alat', $classification);
        }

        return $query->select([
            'area_alat_stocks.alat_id',
            'area_alat_stocks.id as stock_id',
            DB::raw('1 as is_shared_area_stock'),
            'alats.nama as sort_nama',
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $query = $this->buildIndexQuery($request)
            ->orderBy('nama')
            ->orderBy('id');
        $filename = 'data-alat-'.now()->format('Ymd-His').'.csv';
        $delimiter = ';';

        return response()->streamDownload(function () use ($query, $delimiter) {
            $handle = fopen('php://output', 'wb');
            fwrite($handle, "\xEF\xBB\xBF");
            fwrite($handle, "sep={$delimiter}\r\n");
            fputcsv($handle, ['Kode', 'Nama', 'Jenis Alat', 'Klasifikasi Alat', 'Area', 'Total Aset', 'Stok Tersedia'], $delimiter);

            $query->chunk(500, function ($alats) use ($handle, $delimiter) {
                $borrowedMap = $this->borrowedMap($alats->pluck('id')->all());

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
            });

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
            'klasifikasi_alat' => ['required', 'string', 'max:255'],
            'total_aset' => ['required', 'integer', 'min:0'],
            'area_id' => ['required', 'integer', 'exists:areas,id'],
        ]);
        $data['klasifikasi_alat'] = trim($data['klasifikasi_alat']);
        if ($data['klasifikasi_alat'] === '') {
            throw ValidationException::withMessages([
                'klasifikasi_alat' => ['Klasifikasi alat wajib diisi.'],
            ]);
        }
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
            'klasifikasi_alat' => ['required', 'string', 'max:255'],
            'total_aset' => ['required', 'integer', 'min:0'],
            'area_id' => ['required', 'integer', 'exists:areas,id'],
        ]);
        $data['klasifikasi_alat'] = trim($data['klasifikasi_alat']);
        if ($data['klasifikasi_alat'] === '') {
            throw ValidationException::withMessages([
                'klasifikasi_alat' => ['Klasifikasi alat wajib diisi.'],
            ]);
        }
        $data = $this->applyWritableArea($request, $data);

        $alat->update($data);
        $alat->load('area');

        $borrowedMap = $this->borrowedMap([$alat->id]);
        $borrowedQty = $borrowedMap[$alat->id] ?? 0;

        return response()->json($this->formatAlat($alat, $borrowedQty));
    }

    public function import(Request $request, AlatImportService $alatImportService)
    {
        $validated = $request->validate([
            'file' => [
                'required',
                'file',
                'mimes:csv,txt,xlsx',
                'max:5120',
            ],
        ]);

        $path = $validated['file']->store('alat-imports');
        $import = AlatImport::create([
            'user_id' => $request->user()->id,
            'file_name' => $validated['file']->getClientOriginalName(),
            'file_path' => $path,
            'status' => AlatImport::STATUS_PENDING,
        ]);

        ImportAlatJob::dispatch($import->id);

        return response()->json([
            'message' => 'Import sedang diproses.',
            'import' => $alatImportService->formatImport($import),
        ], 202);
    }

    public function downloadImportTemplate(): BinaryFileResponse
    {
        $path = public_path('storage/templates/TEMPLATE-IMPORT-ALAT.xlsx');
        abort_unless(file_exists($path), 404, 'Template import alat belum tersedia. Pastikan file berada di storage/app/public/templates dan jalankan php artisan storage:link.');

        return response()->download($path, basename($path), [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function importStatus(Request $request, AlatImport $import, AlatImportService $alatImportService)
    {
        $actor = $request->user()?->loadMissing('role');
        abort_unless($actor, 401);

        $alatImportService->ensureImportAccessible($import, $actor);

        return response()->json([
            'import' => $alatImportService->formatImport($import->fresh()),
        ]);
    }

    public function downloadImport(Request $request, AlatImport $import, AlatImportService $alatImportService)
    {
        $actor = $request->user()?->loadMissing('role');
        abort_unless($actor, 401);

        $alatImportService->ensureImportAccessible($import, $actor);
        abort_unless(Storage::disk('local')->exists($import->file_path), 404, 'File import tidak ditemukan.');

        return Storage::disk('local')->download($import->file_path, $import->file_name);
    }

    public function destroy(Request $request, Alat $alat)
    {
        $this->ensureToolInAuthorizedArea($request, $alat);

        $alat->delete();

        return response()->json(['message' => 'Alat berhasil dihapus.']);
    }

    public function destroyArea(Request $request)
    {
        abort_unless($this->isSuperAdmin($request), 403, 'Hanya super admin yang dapat menghapus semua alat area aktif.');

        $data = $request->validate([
            'area_id' => ['required', 'integer', 'exists:areas,id'],
        ]);

        $alats = Alat::query()
            ->where('area_id', $data['area_id'])
            ->orderBy('id')
            ->get();

        $deleted = $alats->count();
        if ($deleted === 0) {
            return response()->json([
                'message' => 'Tidak ada alat pada area aktif untuk dihapus.',
                'deleted' => 0,
            ]);
        }

        DB::transaction(function () use ($alats) {
            $alatIds = $alats->pluck('id')->all();

            AreaAlatStock::query()
                ->whereIn('alat_id', $alatIds)
                ->delete();

            foreach ($alats as $alat) {
                $alat->delete();
            }
        });

        return response()->json([
            'message' => 'Semua alat pada area aktif berhasil dihapus.',
            'deleted' => $deleted,
        ]);
    }
}
