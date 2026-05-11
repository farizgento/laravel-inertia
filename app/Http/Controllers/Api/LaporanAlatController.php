<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Alat;
use App\Models\AreaAlatStock;
use App\Models\LaporanAlat;
use App\Models\Role;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LaporanAlatController extends Controller
{
    private function resolveActiveAreaId(Request $request, string $roleKey, $fallbackAreaId): ?int
    {
        if ($roleKey === Role::KEY_SUPER_ADMIN && $request->filled('area_id')) {
            return (int) $request->input('area_id');
        }

        return $fallbackAreaId ? (int) $fallbackAreaId : null;
    }

    private function applyReportAreaFilter(Builder $query, int $areaId): Builder
    {
        return $query->where('area_id', $areaId);
    }

    private function applyAccessibleToolFilter(Builder $query, int $areaId): Builder
    {
        return $query->where(function (Builder $builder) use ($areaId) {
            $builder
                ->where('area_id', $areaId)
                ->orWhereHas('areaStocks', function (Builder $stockQuery) use ($areaId) {
                    $stockQuery
                        ->where('area_id', $areaId)
                        ->where('active', true)
                        ->where('qty', '>', 0);
                });
        });
    }

    public function pendingCounts(Request $request)
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $user->loadMissing('role');
        $roleKey = strtolower((string) ($user->role?->key ?? ''));
        $isSpTool = $roleKey === Role::KEY_SP_TOOL;
        $isPicTools = in_array($roleKey, [Role::KEY_PIC_TOOLS, 'pic_tool'], true);
        $isMgrTool = $roleKey === Role::KEY_MGR_TOOL;
        $isAdmin = in_array($roleKey, [Role::KEY_ADMIN, Role::KEY_SUPER_ADMIN], true);

        if (! ($isSpTool || $isPicTools || $isMgrTool || $isAdmin)) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $areaId = $this->resolveActiveAreaId($request, $roleKey, $user->area_id);

        $baseQuery = LaporanAlat::query()
            ->where('status', 'Dilaporkan');

        if ($isSpTool || $isPicTools || $isMgrTool) {
            if (! $areaId) {
                return response()->json([
                    'kerusakan' => 0,
                    'kehilangan' => 0,
                ]);
            }

            $this->applyReportAreaFilter($baseQuery, $areaId);
        } elseif (! empty($areaId)) {
            $this->applyReportAreaFilter($baseQuery, $areaId);
        }

        $counts = (clone $baseQuery)
            ->selectRaw('kategori, COUNT(*) as total')
            ->groupBy('kategori')
            ->pluck('total', 'kategori');

        return response()->json([
            'kerusakan' => (int) ($counts[LaporanAlat::CATEGORY_KERUSAKAN] ?? 0),
            'kehilangan' => (int) ($counts[LaporanAlat::CATEGORY_KEHILANGAN] ?? 0),
        ]);
    }

    public function indexKehilangan(Request $request)
    {
        return $this->indexByKategori($request, LaporanAlat::CATEGORY_KEHILANGAN);
    }

    public function exportKehilangan(Request $request): StreamedResponse
    {
        return $this->exportByKategori($request, LaporanAlat::CATEGORY_KEHILANGAN);
    }

    public function storeKehilangan(Request $request)
    {
        return $this->storeByKategori($request, LaporanAlat::CATEGORY_KEHILANGAN);
    }

    public function indexKerusakan(Request $request)
    {
        return $this->indexByKategori($request, LaporanAlat::CATEGORY_KERUSAKAN);
    }

    public function exportKerusakan(Request $request): StreamedResponse
    {
        return $this->exportByKategori($request, LaporanAlat::CATEGORY_KERUSAKAN);
    }

    public function storeKerusakan(Request $request)
    {
        return $this->storeByKategori($request, LaporanAlat::CATEGORY_KERUSAKAN);
    }

    public function updateKehilanganStatus(Request $request, LaporanAlat $laporan)
    {
        return $this->updateStatusByKategori($request, $laporan, LaporanAlat::CATEGORY_KEHILANGAN);
    }

    public function updateKerusakanStatus(Request $request, LaporanAlat $laporan)
    {
        return $this->updateStatusByKategori($request, $laporan, LaporanAlat::CATEGORY_KERUSAKAN);
    }

    private function indexByKategori(Request $request, string $kategori)
    {
        $context = $this->resolveKategoriContext($request);
        $perPage = (int) $request->query('per_page', 0);
        $shouldPaginate = $request->has('per_page') || $request->has('page');
        $perPageNormalized = $shouldPaginate ? ($perPage > 0 ? min($perPage, 100) : 10) : 0;

        if ($this->shouldReturnEmptyResult($context)) {
            if ($shouldPaginate) {
                return [
                    'data' => [],
                    'meta' => [
                        'current_page' => 1,
                        'last_page' => 1,
                        'per_page' => $perPageNormalized ?: 10,
                        'total' => 0,
                    ],
                ];
            }

            return collect();
        }

        $query = $this->buildKategoriQuery($request, $kategori, $context);

        if ($shouldPaginate) {
            $laporans = $query->paginate($perPageNormalized ?: 10);
            $data = $laporans->getCollection()
                ->map(fn (LaporanAlat $laporan) => $this->mapLaporan($laporan))
                ->values();

            return [
                'data' => $data,
                'meta' => [
                    'current_page' => $laporans->currentPage(),
                    'last_page' => $laporans->lastPage(),
                    'per_page' => $laporans->perPage(),
                    'total' => $laporans->total(),
                ],
            ];
        }

        return $query->get()
            ->map(fn (LaporanAlat $laporan) => $this->mapLaporan($laporan))
            ->values();
    }

    private function exportByKategori(Request $request, string $kategori): StreamedResponse
    {
        $context = $this->resolveKategoriContext($request);
        $filename = 'laporan-' . $kategori . '-' . now()->format('Ymd-His') . '.csv';

        return response()->streamDownload(function () use ($request, $kategori, $context) {
            $handle = fopen('php://output', 'w');
            if ($handle === false) {
                return;
            }

            fwrite($handle, "\xEF\xBB\xBF");
            fwrite($handle, "sep=;\n");

            fputcsv($handle, [
                'Tanggal',
                'ID',
                'Kategori',
                'Alat',
                'Kode Alat',
                'Area',
                'Pelapor',
                'Jumlah',
                'Status',
                'Deskripsi',
                'File Foto',
                'URL Foto',
            ], ';');

            if (! $this->shouldReturnEmptyResult($context)) {
                $this->buildKategoriQuery($request, $kategori, $context)
                    ->chunk(500, function ($laporans) use ($handle) {
                        foreach ($laporans as $laporan) {
                            $row = $this->mapLaporan($laporan);

                            fputcsv($handle, [
                                $row['created_at'] ?? '-',
                                $row['id'] ?? '',
                                Str::headline((string) ($row['kategori'] ?? '')),
                                $row['alat_nama'] ?? '-',
                                $row['alat_kode'] ?? '-',
                                $row['area_name'] ?? '-',
                                $row['user_name'] ?? '-',
                                $row['jumlah'] ?? 0,
                                $row['status'] ?? '-',
                                $row['deskripsi'] ?? '-',
                                $row['original_name'] ?? '-',
                                $row['url'] ?? '',
                            ], ';');
                        }
                    });
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function storeByKategori(Request $request, string $kategori)
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $user->loadMissing('role');
        $roleKey = strtolower((string) ($user->role?->key ?? ''));
        $isSpTool = $roleKey === Role::KEY_SP_TOOL;
        $isPicTools = in_array($roleKey, [Role::KEY_PIC_TOOLS, 'pic_tool'], true);
        $isMgrTool = $roleKey === Role::KEY_MGR_TOOL;
        $isAdmin = in_array($roleKey, [Role::KEY_ADMIN, Role::KEY_SUPER_ADMIN], true);
        $targetAreaId = $this->resolveActiveAreaId($request, $roleKey, $user->area_id);

        if (! ($isSpTool || $isPicTools || $isMgrTool || $isAdmin)) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        if (! $targetAreaId && ! $isAdmin) {
            return response()->json(['message' => 'Area tidak ditemukan.'], 403);
        }

        $validated = $request->validate([
            'alat_id' => ['required', 'integer', 'exists:alats,id'],
            'deskripsi' => ['required', 'string', 'max:1000'],
            'jumlah' => ['required', 'integer', 'min:1'],
            'foto' => ['required', 'image', 'max:5120'],
        ]);

        $alat = Alat::query()
            ->where('id', $validated['alat_id'])
            ->when($targetAreaId, function (Builder $query) use ($targetAreaId) {
                $this->applyAccessibleToolFilter($query, $targetAreaId);
            }, function (Builder $query) use ($isAdmin, $user) {
                if (! $isAdmin) {
                    $this->applyAccessibleToolFilter($query, (int) $user->area_id);
                }
            })
            ->first();

        if (! $alat) {
            throw ValidationException::withMessages([
                'alat_id' => [$isAdmin ? 'Alat tidak ditemukan.' : 'Alat tidak ditemukan di area anda.'],
            ]);
        }

        $sourcePeminjamanId = null;
        if ($targetAreaId && (int) $alat->area_id !== (int) $targetAreaId) {
            $sourcePeminjamanId = AreaAlatStock::query()
                ->where('area_id', $targetAreaId)
                ->where('alat_id', $alat->id)
                ->where('active', true)
                ->where('qty', '>', 0)
                ->value('source_peminjaman_id');
        }

        $file = $validated['foto'];
        if (! $file instanceof UploadedFile) {
            $label = $kategori === LaporanAlat::CATEGORY_KEHILANGAN ? 'kehilangan' : 'kerusakan';
            throw ValidationException::withMessages([
                'foto' => ["Foto {$label} tidak valid."],
            ]);
        }

        $dir = "{$kategori}/{$alat->id}";
        $extension = $file->getClientOriginalExtension() ?: 'jpg';
        $filename = Str::uuid()->toString() . '.' . $extension;
        $path = $file->storeAs($dir, $filename, 'public');

        $laporan = LaporanAlat::create([
            'kategori' => $kategori,
            'deskripsi' => $validated['deskripsi'],
            'status' => 'Dilaporkan',
            'jumlah' => $validated['jumlah'],
            'alat_id' => $alat->id,
            'user_id' => $user->id,
            'area_id' => $targetAreaId,
            'source_peminjaman_id' => $sourcePeminjamanId,
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime' => $file->getClientMimeType(),
            'size' => $file->getSize(),
        ]);

        return response()->json($this->mapLaporan($laporan->loadMissing(['alat.area', 'area', 'user'])), 201);
    }

    private function updateStatusByKategori(Request $request, LaporanAlat $laporan, string $kategori)
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        if ($laporan->kategori !== $kategori) {
            return response()->json(['message' => 'Laporan tidak ditemukan.'], 404);
        }

        $user->loadMissing('role');
        $roleKey = strtolower((string) ($user->role?->key ?? ''));
        $isSpTool = $roleKey === Role::KEY_SP_TOOL;
        $isPicTools = in_array($roleKey, [Role::KEY_PIC_TOOLS, 'pic_tool'], true);
        $isMgrTool = $roleKey === Role::KEY_MGR_TOOL;
        $isAdmin = in_array($roleKey, [Role::KEY_ADMIN, Role::KEY_SUPER_ADMIN], true);
        $targetAreaId = $this->resolveActiveAreaId($request, $roleKey, $user->area_id);

        if (! ($isSpTool || $isPicTools || $isMgrTool || $isAdmin)) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $validated = $request->validate([
            'status' => ['required', 'in:Disetujui,Ditolak,Selesai'],
        ]);

        $laporan->loadMissing('alat.area', 'area');
        $alat = $laporan->alat;
        if (! $alat) {
            return response()->json(['message' => 'Alat tidak ditemukan.'], 422);
        }

        $laporanAreaId = (int) ($laporan->area_id ?? $alat->area_id);
        if (($isSpTool || $isPicTools || $isMgrTool) && (! $targetAreaId || $laporanAreaId !== (int) $targetAreaId)) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $targetStatus = $validated['status'];

        DB::transaction(function () use ($laporan, $alat, $targetStatus) {
            $lockedLaporan = LaporanAlat::query()
                ->whereKey($laporan->id)
                ->lockForUpdate()
                ->firstOrFail();

            $lockedAlat = Alat::query()
                ->whereKey($alat->id)
                ->lockForUpdate()
                ->firstOrFail();

            $wasApproved = $lockedLaporan->status === 'Disetujui';
            $willBeApproved = $targetStatus === 'Disetujui';
            $jumlah = (int) $lockedLaporan->jumlah;

            if (! $wasApproved && $willBeApproved) {
                if ((int) $lockedAlat->total_aset < $jumlah) {
                    throw ValidationException::withMessages([
                        'status' => ['Total aset alat tidak mencukupi untuk disetujui.'],
                    ]);
                }

                $lockedAlat->decrement('total_aset', $jumlah);
                if ($lockedLaporan->area_id && (int) $lockedLaporan->area_id !== (int) $lockedAlat->area_id) {
                    $stock = AreaAlatStock::query()
                        ->where('area_id', $lockedLaporan->area_id)
                        ->where('alat_id', $lockedAlat->id)
                        ->where('active', true)
                        ->lockForUpdate()
                        ->first();
                    if ($stock) {
                        $nextQty = max((int) $stock->qty - $jumlah, 0);
                        $stock->update([
                            'qty' => $nextQty,
                            'active' => $nextQty > 0,
                        ]);
                    }
                }
            }

            if ($wasApproved && ! $willBeApproved) {
                $lockedAlat->increment('total_aset', $jumlah);
                if ($lockedLaporan->area_id && (int) $lockedLaporan->area_id !== (int) $lockedAlat->area_id) {
                    $stock = AreaAlatStock::query()
                        ->firstOrNew([
                            'area_id' => $lockedLaporan->area_id,
                            'alat_id' => $lockedAlat->id,
                            'source_peminjaman_id' => $lockedLaporan->source_peminjaman_id,
                        ]);
                    $stock->qty = (int) ($stock->qty ?? 0) + $jumlah;
                    $stock->active = true;
                    $stock->save();
                }
            }

            $lockedLaporan->update([
                'status' => $targetStatus,
            ]);
        });

        return response()->json(
            $this->mapLaporan(
                $laporan->fresh(['alat.area', 'area', 'user'])
            )
        );
    }

    private function resolveKategoriContext(Request $request): array
    {
        $user = $request->user();
        if (! $user) {
            abort(401, 'Unauthenticated.');
        }

        $user->loadMissing('role');
        $roleKey = strtolower((string) ($user->role?->key ?? ''));
        $isSpTool = $roleKey === Role::KEY_SP_TOOL;
        $isPicTools = in_array($roleKey, [Role::KEY_PIC_TOOLS, 'pic_tool'], true);
        $isMgrTool = $roleKey === Role::KEY_MGR_TOOL;
        $isAdmin = in_array($roleKey, [Role::KEY_ADMIN, Role::KEY_SUPER_ADMIN], true);

        abort_unless($isSpTool || $isPicTools || $isMgrTool || $isAdmin, 403, 'Forbidden.');

        return [
            'role_key' => $roleKey,
            'is_sp_tool' => $isSpTool,
            'is_pic_tools' => $isPicTools,
            'is_mgr_tool' => $isMgrTool,
            'is_admin' => $isAdmin,
            'area_id' => $this->resolveActiveAreaId($request, $roleKey, $user->area_id),
        ];
    }

    private function shouldReturnEmptyResult(array $context): bool
    {
        return ($context['is_sp_tool'] || $context['is_pic_tools'] || $context['is_mgr_tool'])
            && empty($context['area_id']);
    }

    private function buildKategoriQuery(Request $request, string $kategori, array $context): Builder
    {
        $search = trim((string) $request->query('search', ''));
        $alatId = $request->query('alat_id');
        $status = trim((string) $request->query('status', ''));
        $areaId = $context['area_id'];

        $query = LaporanAlat::query()
            ->where('kategori', $kategori)
            ->with(['alat.area', 'area', 'user'])
            ->orderByDesc('created_at');

        if ($context['is_sp_tool'] || $context['is_pic_tools'] || $context['is_mgr_tool']) {
            $this->applyReportAreaFilter($query, $areaId);
        } elseif (! empty($areaId)) {
            $this->applyReportAreaFilter($query, $areaId);
        }

        if (! empty($alatId)) {
            $query->where('alat_id', $alatId);
        }

        if ($status !== '' && strtolower($status) !== 'semua') {
            $query->where('status', $status);
        }

        if ($search !== '') {
            $query->where(function (Builder $sub) use ($search) {
                $sub->where('deskripsi', 'like', '%' . $search . '%')
                    ->orWhere('id', $search)
                    ->orWhereHas('alat', function (Builder $alatQuery) use ($search) {
                        $alatQuery->where('nama', 'like', '%' . $search . '%');
                    })
                    ->orWhereHas('alat.area', function (Builder $areaQuery) use ($search) {
                        $areaQuery->where('name', 'like', '%' . $search . '%');
                    })
                    ->orWhereHas('user', function (Builder $userQuery) use ($search) {
                        $userQuery->where('name', 'like', '%' . $search . '%');
                    });
            });
        }

        return $query;
    }

    private function mapLaporan(LaporanAlat $laporan): array
    {
        $alat = $laporan->alat;
        $area = $laporan->area ?: $alat?->area;
        $reporter = $laporan->user;

        return [
            'id' => $laporan->id,
            'kategori' => $laporan->kategori,
            'alat_id' => $laporan->alat_id,
            'alat_nama' => $alat?->nama ?? '-',
            'alat_kode' => $alat?->kode ?? '-',
            'area_id' => $area?->id,
            'area_name' => $area?->name ?? '-',
            'user_id' => $laporan->user_id,
            'user_name' => $reporter?->name ?? '-',
            'deskripsi' => $laporan->deskripsi,
            'jumlah' => (int) $laporan->jumlah,
            'status' => $laporan->status ?? 'Dilaporkan',
            'path' => $laporan->path,
            'url' => $laporan->path ? url('/storage/' . ltrim($laporan->path, '/')) : null,
            'original_name' => $laporan->original_name,
            'mime' => $laporan->mime,
            'size' => $laporan->size,
            'created_at' => $laporan->created_at
                ? $laporan->created_at->format('d M Y H:i')
                : null,
        ];
    }
}
