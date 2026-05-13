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
use Symfony\Component\HttpFoundation\StreamedResponse;

class AlatController extends Controller
{
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

    private function canBrowseInterAreaSource(Request $request): bool
    {
        $roleKey = strtolower((string) ($request->user()?->role?->key ?? ''));

        return in_array($roleKey, [Role::KEY_PIC_TOOLS, 'pic_tool', Role::KEY_ADMIN, Role::KEY_SUPER_ADMIN], true)
            && $request->boolean('inter_area_source');
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
                        WHEN pem.status IN ('" . Peminjaman::STATUS_PERLU_DISETUJUI . "', '" . Peminjaman::STATUS_PERLU_DIREVIEW . "') THEN items.qty
                        WHEN pem.status IN ('" . Peminjaman::STATUS_DIPESAN . "', '" . Peminjaman::STATUS_DIKIRIM . "') THEN COALESCE(items.approved_qty, 0)
                        WHEN pem.status IN ('" . Peminjaman::STATUS_DITERIMA . "', '" . Peminjaman::STATUS_DIKEMBALIKAN_PARTIALS . "') THEN GREATEST(COALESCE(items.approved_qty, 0) - COALESCE(items.returned_qty, 0), 0)
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
                        WHEN pem.status IN ('" . Peminjaman::STATUS_PERLU_DISETUJUI . "', '" . Peminjaman::STATUS_PERLU_DIREVIEW . "') THEN items.qty
                        WHEN pem.status IN ('" . Peminjaman::STATUS_DIPESAN . "', '" . Peminjaman::STATUS_DIKIRIM . "') THEN COALESCE(items.approved_qty, 0)
                        WHEN pem.status IN ('" . Peminjaman::STATUS_DITERIMA . "', '" . Peminjaman::STATUS_DIKEMBALIKAN_PARTIALS . "') THEN GREATEST(COALESCE(items.approved_qty, 0) - COALESCE(items.returned_qty, 0), 0)
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
        $areaId = $request->query('area_id');
        $authorizedAreaId = $this->getAuthorizedAreaId($request);

        if ($request->user() && $authorizedAreaId !== null && ! $this->canBrowseInterAreaSource($request)) {
            $areaId = $authorizedAreaId;
        }

        $query = Alat::query()->with('area');

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
        $areaId = $request->query('area_id');
        if ($request->user() && $authorizedAreaId !== null && ! $this->canBrowseInterAreaSource($request)) {
            $areaId = $authorizedAreaId;
        }

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
        $ownedAlats = $ownedQuery->orderBy('nama')->get();
        $sharedStocks = AreaAlatStock::query()
            ->with(['alat.area', 'sourcePeminjaman'])
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

        if (! $shouldPaginate) {
            return $data;
        }

        $perPage = $perPageNormalized ?: 8;
        $currentPage = max((int) $request->query('page', 1), 1);
        $total = $data->count();
        $pageData = $data->slice(($currentPage - 1) * $perPage, $perPage)->values();

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
}
