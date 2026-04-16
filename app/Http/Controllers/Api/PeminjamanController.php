<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Alat;
use App\Models\LaporanAlat;
use App\Models\Peminjaman;
use App\Models\PeminjamanItem;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Validation\ValidationException;

class PeminjamanController extends Controller
{
    private function buildIndexQuery(Request $request)
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
        $isUser = $roleKey === Role::KEY_USER;
        $isAdmin = $roleKey === Role::KEY_ADMIN;
        $isSuperAdmin = $roleKey === Role::KEY_SUPER_ADMIN;
        $areaIdParam = $request->query('area_id');

        $search = trim((string) $request->query('search', ''));
        $status = trim((string) $request->query('status', ''));

        $query = Peminjaman::query()
            ->with(['items.alat', 'items.photos', 'suratJalan', 'user', 'reviewer'])
            ->orderByDesc('created_at');

        if ($isSuperAdmin || $isAdmin) {
            if (! empty($areaIdParam)) {
                $query->where('area_id', $areaIdParam);
            }
        } elseif ($isSpTool || $isPicTools || $isMgrTool) {
            $areaId = $user->area_id;
            if (! $areaId) {
                return null;
            }
            $query->where('area_id', $areaId);
        } elseif ($isUser) {
            $query->where('user_id', $user->id);
        } else {
            abort(403, 'Forbidden.');
        }

        if ($search !== '') {
            $query->where(function ($sub) use ($search) {
                $sub->where('keperluan', 'like', '%' . $search . '%')
                    ->orWhere('id', $search);
            });
        }

        if ($status !== '' && $status !== 'Semua') {
            $query->where('status', $status);
        }

        return $query;
    }

    private function transformPeminjaman(Peminjaman $peminjaman): array
    {
        $alatIds = $peminjaman->items
            ->pluck('alat_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->values();

        $laporansByAlat = LaporanAlat::query()
            ->with('alat')
            ->where('user_id', $peminjaman->user_id)
            ->when($alatIds->isNotEmpty(), function ($reportQuery) use ($alatIds) {
                $reportQuery->whereIn('alat_id', $alatIds->all());
            }, function ($reportQuery) {
                $reportQuery->whereRaw('1 = 0');
            })
            ->when($peminjaman->created_at && $peminjaman->updated_at, function ($reportQuery) use ($peminjaman) {
                $reportQuery->whereBetween('created_at', [
                    $peminjaman->created_at,
                    $peminjaman->updated_at,
                ]);
            })
            ->orderByDesc('created_at')
            ->get()
            ->groupBy('alat_id');

        $tools = $peminjaman->items->map(function (PeminjamanItem $item) use ($laporansByAlat) {
            $alat = $item->alat;
            $toolReports = collect($laporansByAlat->get($item->alat_id, []))
                ->map(function (LaporanAlat $laporan) use ($alat) {
                    return [
                        'id' => $laporan->id,
                        'alat_id' => $laporan->alat_id,
                        'alat_name' => $alat?->nama ?? '-',
                        'alat_code' => $alat ? sprintf('ALT-%03d', $alat->id) : '-',
                        'kategori' => $laporan->kategori,
                        'status' => $laporan->status ?? 'Dilaporkan',
                        'jumlah' => (int) $laporan->jumlah,
                        'deskripsi' => $laporan->deskripsi,
                        'created_at' => $laporan->created_at
                            ? $laporan->created_at->format('d M Y H:i')
                            : null,
                        'path' => $laporan->path,
                        'url' => $laporan->path
                            ? url('/storage/' . ltrim($laporan->path, '/'))
                            : null,
                        'original_name' => $laporan->original_name,
                    ];
                })
                ->values();

            return [
                'alat_id' => $item->alat_id,
                'name' => $alat?->nama ?? '-',
                'code' => $alat ? sprintf('ALT-%03d', $alat->id) : '-',
                'qty' => (int) $item->qty,
                'approved_qty' => (int) ($item->approved_qty ?? 0),
                'returned_qty' => (int) ($item->returned_qty ?? 0),
                'remaining_qty' => max((int) ($item->approved_qty ?? 0) - (int) ($item->returned_qty ?? 0), 0),
                'review_status' => $item->review_status ?? 'Menunggu Review',
                'rejection_reason' => $item->rejection_reason,
                'photos' => $item->photos
                    ? $item->photos->map(fn ($photo) => [
                        'id' => $photo->id,
                        'path' => $photo->path,
                        'url' => url('/storage/' . ltrim($photo->path, '/')),
                        'original_name' => $photo->original_name,
                    ])->values()
                    : [],
                'reports' => $toolReports,
            ];
        })->values();

        $reports = $tools
            ->flatMap(fn (array $tool) => $tool['reports'] ?? [])
            ->values();

        return [
            'id' => $peminjaman->id,
            'title' => $peminjaman->keperluan,
            'user_name' => $peminjaman->user?->name ?? '-',
            'reviewed_by_name' => $peminjaman->reviewer?->name ?? '-',
            'created_at' => $peminjaman->created_at
                ? $peminjaman->created_at->format('d M Y H:i')
                : null,
            'borrow_date' => $peminjaman->tanggal_pinjam
                ? $peminjaman->tanggal_pinjam->format('d M Y')
                : null,
            'return_date' => $peminjaman->tanggal_kembali
                ? $peminjaman->tanggal_kembali->format('d M Y')
                : null,
            'item_count' => $peminjaman->items->sum('qty'),
            'status' => $peminjaman->status,
            'pengirim_nama' => $peminjaman->suratJalan?->pengirim_nama,
            'surat_jalan_path' => $peminjaman->suratJalan?->path,
            'surat_jalan_url' => $peminjaman->suratJalan?->path
                ? url('/storage/' . ltrim($peminjaman->suratJalan->path, '/'))
                : null,
            'tools' => $tools,
            'reports' => $reports,
        ];
    }

    public function index(Request $request)
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $perPage = (int) $request->query('per_page', 8);
        if ($perPage < 1) {
            $perPage = 8;
        }
        if ($perPage > 100) {
            $perPage = 100;
        }

        $query = $this->buildIndexQuery($request);
        if (! $query) {
            return response()->json([
                'data' => [],
                'current_page' => 1,
                'last_page' => 1,
                'per_page' => $perPage,
                'total' => 0,
            ]);
        }

        $peminjamans = $query->paginate($perPage);

        $peminjamans->setCollection(
            $peminjamans->getCollection()->map(fn (Peminjaman $peminjaman) => $this->transformPeminjaman($peminjaman))->values()
        );

        return $peminjamans;
    }

    public function export(Request $request): StreamedResponse
    {
        $query = $this->buildIndexQuery($request);
        if (! $query) {
            $query = Peminjaman::query()->whereRaw('1 = 0');
        }

        $rows = $query->get()->flatMap(function (Peminjaman $peminjaman) {
            $createdAt = $peminjaman->created_at?->format('d/m/Y H:i') ?? '-';
            $periode = trim(($peminjaman->tanggal_pinjam?->format('d/m/Y') ?? '-') . ' - ' . ($peminjaman->tanggal_kembali?->format('d/m/Y') ?? '-'));
            $base = [
                'ID Peminjaman' => $peminjaman->id,
                'Nama Peminjaman' => $peminjaman->keperluan,
                'Tanggal Dibuat' => $createdAt,
                'Periode Pinjaman' => $periode,
                'Nama Peminjam' => $peminjaman->user?->name ?? '-',
                'Nama Reviewer' => $peminjaman->reviewer?->name ?? '-',
                'Status' => $peminjaman->status,
            ];

            $tools = $peminjaman->items;
            if ($tools->isEmpty()) {
                return [[...$base, 'Nama Alat' => '-']];
            }

            return $tools->map(function (PeminjamanItem $item) use ($base) {
                return [...$base, 'Nama Alat' => $item->alat?->nama ?? '-'];
            });
        })->values();

        $filename = 'riwayat-peminjaman-' . now()->format('Ymd-His') . '.csv';

        return response()->streamDownload(function () use ($rows) {
            $handle = fopen('php://output', 'wb');
            fwrite($handle, "\xEF\xBB\xBF");
            fwrite($handle, "sep=;\n");

            fputcsv($handle, [
                'ID Peminjaman',
                'Nama Peminjaman',
                'Tanggal Dibuat',
                'Periode Pinjaman',
                'Nama Peminjam',
                'Nama Reviewer',
                'Status',
                'Nama Alat',
            ], ';');

            foreach ($rows as $row) {
                fputcsv($handle, [
                    $row['ID Peminjaman'] ?? '',
                    $row['Nama Peminjaman'] ?? '',
                    $row['Tanggal Dibuat'] ?? '',
                    $row['Periode Pinjaman'] ?? '',
                    $row['Nama Peminjam'] ?? '',
                    $row['Nama Reviewer'] ?? '',
                    $row['Status'] ?? '',
                    $row['Nama Alat'] ?? '',
                ], ';');
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'tanggal_pinjam' => ['required', 'date'],
            'tanggal_kembali' => ['required', 'date', 'after_or_equal:tanggal_pinjam'],
            'keperluan' => ['required', 'string', 'max:1000'],
            'area_id' => ['nullable', 'integer', 'exists:areas,id'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.id' => ['required', 'integer'],
            'items.*.qty' => ['required', 'integer', 'min:1'],
        ]);

        $user = $request->user();
        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $user->loadMissing('role');
        $roleKey = strtolower((string) ($user->role?->key ?? ''));
        $isSuperAdmin = $roleKey === Role::KEY_SUPER_ADMIN;
        $targetAreaId = $isSuperAdmin
            ? ($validated['area_id'] ?? null)
            : $user->area_id;

        if (! $targetAreaId) {
            throw ValidationException::withMessages([
                'area_id' => ['Area peminjaman wajib dipilih.'],
            ]);
        }

        $items = collect($validated['items'])->keyBy('id');

        $peminjaman = DB::transaction(function () use ($items, $validated, $user, $targetAreaId) {
            $alats = Alat::query()
                ->whereIn('id', $items->keys()->all())
                ->where('area_id', $targetAreaId)
                ->lockForUpdate()
                ->get();

            if ($alats->count() !== $items->count()) {
                throw ValidationException::withMessages([
                    'items' => ['Ada alat yang tidak ditemukan.'],
                ]);
            }

            $borrowedMap = $this->borrowedMap($alats->pluck('id')->all());

            foreach ($alats as $alat) {
                $qty = (int) $items[$alat->id]['qty'];
                if ($qty < 1) {
                    throw ValidationException::withMessages([
                        'items' => ['Jumlah alat harus lebih dari 0.'],
                    ]);
                }
                $borrowedQty = $borrowedMap[$alat->id] ?? 0;
                $available = max(((int) $alat->total_aset) - $borrowedQty, 0);
                if ($available < $qty) {
                    throw ValidationException::withMessages([
                        'items' => ["Stok {$alat->nama} tidak mencukupi."],
                    ]);
                }
            }

            $peminjaman = Peminjaman::create([
                'user_id' => $user->id,
                'area_id' => $targetAreaId,
                'status' => 'Menunggu Review',
                'tanggal_pinjam' => $validated['tanggal_pinjam'],
                'tanggal_kembali' => $validated['tanggal_kembali'],
                'keperluan' => $validated['keperluan'],
            ]);

            $now = now();
            $itemRows = [];

            foreach ($alats as $alat) {
                $qty = (int) $items[$alat->id]['qty'];

                $itemRows[] = [
                    'peminjaman_id' => $peminjaman->id,
                    'alat_id' => $alat->id,
                    'qty' => $qty,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            if ($itemRows) {
                PeminjamanItem::insert($itemRows);
            }

            return $peminjaman;
        });

        return response()->json([
            'id' => $peminjaman->id,
            'status' => $peminjaman->status,
            'tanggal_pinjam' => $peminjaman->tanggal_pinjam?->toDateString(),
            'tanggal_kembali' => $peminjaman->tanggal_kembali?->toDateString(),
        ], 201);
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
                        WHEN pem.status = '" . Peminjaman::STATUS_MENUNGGU_REVIEW . "' THEN items.qty
                        WHEN pem.status IN ('" . Peminjaman::STATUS_DIPESAN . "', '" . Peminjaman::STATUS_DISIAPKAN . "', '" . Peminjaman::STATUS_TERKIRIM . "') THEN COALESCE(items.approved_qty, 0)
                        WHEN pem.status IN ('" . Peminjaman::STATUS_DITERIMA . "', '" . Peminjaman::STATUS_DIKEMBALIKAN_PARTIALS . "') THEN GREATEST(COALESCE(items.approved_qty, 0) - COALESCE(items.returned_qty, 0), 0)
                        ELSE 0
                    END) as total"
                )
            )
            ->pluck('total', 'alat_id')
            ->map(fn ($value) => (int) $value)
            ->all();
    }
}
