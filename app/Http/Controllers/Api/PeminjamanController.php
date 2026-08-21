<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Alat;
use App\Models\AreaAlatStock;
use App\Models\LaporanAlat;
use App\Models\Peminjaman;
use App\Models\PeminjamanItem;
use App\Models\Role;
use App\Models\SuratJalan;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class PeminjamanController extends Controller
{
    private function relatedToArea(Peminjaman $peminjaman, int $areaId): bool
    {
        return (int) $peminjaman->area_id === $areaId
            || (int) $peminjaman->requester_area_id === $areaId;
    }

    private function ensureAdminCanAccessPeminjaman(Request $request, Peminjaman $peminjaman): void
    {
        $user = $request->user();
        $roleKey = strtolower((string) ($user?->role?->key ?? ''));

        if ($roleKey === Role::KEY_SUPER_ADMIN) {
            return;
        }

        if ($roleKey === Role::KEY_ADMIN) {
            abort_unless($user?->area_id && $this->relatedToArea($peminjaman, (int) $user->area_id), 403);
        }
    }

    private function buildIndexQuery(Request $request)
    {
        $user = $request->user();
        if (! $user) {
            abort(401, 'Unauthenticated.');
        }

        $user->loadMissing('role');
        $roleKey = strtolower((string) ($user->role?->key ?? ''));
        $isGuest = $roleKey === Role::KEY_GUEST;
        $isSpTool = $roleKey === Role::KEY_SP_TOOL;
        $isPicTools = $roleKey === Role::KEY_PIC_TOOL;
        $isMgrTool = $roleKey === Role::KEY_MGR_TOOL;
        $isUser = $roleKey === Role::KEY_USER;
        $isAdmin = $roleKey === Role::KEY_ADMIN;
        $isSuperAdmin = $roleKey === Role::KEY_SUPER_ADMIN;
        $areaIdParam = $request->query('area_id');

        $search = trim((string) $request->query('search', ''));
        $status = trim((string) $request->query('status', ''));
        $kategori = trim((string) $request->query('kategori', ''));

        $query = Peminjaman::query()
            ->with(['items.alat.area', 'suratJalans.photos', 'user', 'reviewer', 'requesterReviewer', 'area', 'requesterArea'])
            ->orderByDesc('created_at');

        if ($isGuest) {
            $areaId = ! empty($areaIdParam) ? (int) $areaIdParam : (int) $user->area_id;
            if (! $areaId) {
                return null;
            }
            $query->where(function ($sub) use ($areaId) {
                $sub->where('area_id', $areaId)
                    ->orWhere('requester_area_id', $areaId);
            });
        } elseif ($isSuperAdmin) {
            if (! empty($areaIdParam)) {
                $query->where('area_id', $areaIdParam);
            }
        } elseif ($isAdmin) {
            $areaId = $user->area_id;
            if (! $areaId) {
                return null;
            }
            $query->where(function ($sub) use ($areaId) {
                $sub->where('area_id', $areaId)
                    ->orWhere('requester_area_id', $areaId);
            });
        } elseif ($isSpTool || $isPicTools || $isMgrTool) {
            $areaId = $user->area_id;
            if (! $areaId) {
                return null;
            }
            $query->where(function ($sub) use ($areaId) {
                $sub->where('area_id', $areaId)
                    ->orWhere('requester_area_id', $areaId);
            });
        } elseif ($isUser) {
            $query->where('user_id', $user->id);
        } else {
            abort(403, 'Forbidden.');
        }

        if ($search !== '') {
            $query->where(function ($sub) use ($search) {
                $sub->where('pekerjaan', 'like', '%'.$search.'%')
                    ->orWhere('id', $search);
            });
        }

        if ($status !== '' && $status !== 'Semua') {
            $query->where('status', $status);
        }

        if ($kategori !== '' && $kategori !== 'Semua') {
            $query->where('kategori', $kategori);
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
            ->with('alat.area')
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
                        'alat_code' => $alat?->kode ?? '-',
                        'kategori' => $laporan->kategori,
                        'status' => $laporan->status ?? 'Dilaporkan',
                        'jumlah' => (int) $laporan->jumlah,
                        'deskripsi' => $laporan->deskripsi,
                        'created_at' => $laporan->created_at
                            ? $laporan->created_at->format('d M Y H:i')
                            : null,
                        'path' => $laporan->path,
                        'url' => $laporan->path
                            ? url('/storage/'.ltrim($laporan->path, '/'))
                            : null,
                        'original_name' => $laporan->original_name,
                    ];
                })
                ->values();

            return [
                'alat_id' => $item->alat_id,
                'name' => $alat?->nama ?? '-',
                'code' => $alat?->kode ?? '-',
                'qty' => (int) $item->qty,
                'approved_qty' => (int) ($item->approved_qty ?? 0),
                'returned_qty' => (int) ($item->returned_qty ?? 0),
                'remaining_qty' => max((int) ($item->approved_qty ?? 0) - (int) ($item->returned_qty ?? 0), 0),
                'review_status' => $item->review_status ?? 'Menunggu Review',
                'rejection_reason' => $item->rejection_reason,
                'reports' => $toolReports,
            ];
        })->values();

        $reports = $tools
            ->flatMap(fn (array $tool) => $tool['reports'] ?? [])
            ->values();

        $suratJalans = $peminjaman->suratJalans->values();
        $suratJalanPengiriman = $suratJalans
            ->firstWhere('jenis', SuratJalan::TYPE_SHIPMENT);
        $suratJalanPengembalian = $suratJalans
            ->where('jenis', SuratJalan::TYPE_RETURN)
            ->last();
        $returnDocumentIndex = 0;
        $suratJalanItems = $suratJalans
            ->filter(fn (SuratJalan $suratJalan) => $suratJalan->isShipment() || $suratJalan->isReturn())
            ->map(function (SuratJalan $suratJalan) use (&$returnDocumentIndex) {
                $isShipment = $suratJalan->isShipment();
                if ($suratJalan->isReturn()) {
                    $returnDocumentIndex++;
                }

                return [
                    'id' => $suratJalan->id,
                    'type' => $suratJalan->jenis,
                    'label' => $isShipment
                        ? 'Surat Jalan Pengiriman'
                        : 'Surat Jalan Pengembalian '.$returnDocumentIndex,
                    'pengirim_nama' => $suratJalan->pengirim_nama,
                    'path' => $suratJalan->path,
                    'url' => $suratJalan->download_url,
                    'download_url' => $suratJalan->download_url,
                    'original_name' => $suratJalan->original_name,
                    'mime_type' => $suratJalan->mime,
                    'photo_count' => $suratJalan->relationLoaded('photos')
                        ? $suratJalan->photos->count()
                        : $suratJalan->photos()->count(),
                    'created_at' => $suratJalan->created_at
                        ? $suratJalan->created_at->format('d M Y H:i')
                        : null,
                ];
            })
            ->values();

        return [
            'id' => $peminjaman->id,
            'title' => $peminjaman->pekerjaan,
            'user_name' => $peminjaman->user?->name ?? '-',
            'area_id' => $peminjaman->area_id,
            'area_name' => $peminjaman->area?->name ?? '-',
            'requester_area_id' => $peminjaman->requester_area_id,
            'requester_area_name' => $peminjaman->requesterArea?->name ?? '-',
            'is_inter_area' => (bool) $peminjaman->is_inter_area,
            'reviewed_by_name' => $peminjaman->reviewer?->name ?? '-',
            'requester_reviewed_by_name' => $peminjaman->requesterReviewer?->name ?? '-',
            'created_at' => $peminjaman->created_at
                ? $peminjaman->created_at->format('d M Y H:i')
                : null,
            'borrow_date' => $peminjaman->tanggal_pinjam
                ? $peminjaman->tanggal_pinjam->format('d M Y')
                : null,
            'borrow_date_value' => $peminjaman->tanggal_pinjam?->toDateString(),
            'return_date' => $peminjaman->tanggal_kembali
                ? $peminjaman->tanggal_kembali->format('d M Y')
                : null,
            'return_date_value' => $peminjaman->tanggal_kembali?->toDateString(),
            'item_count' => $peminjaman->items->sum('qty'),
            'status' => $peminjaman->status,
            'kategori' => $peminjaman->kategori ?? Peminjaman::KATEGORI_INTRA_AREA,
            'pengirim_nama' => $suratJalanPengiriman?->pengirim_nama,
            'surat_jalan_path' => $suratJalanPengiriman?->path,
            'surat_jalan_url' => $suratJalanPengiriman?->download_url,
            'surat_jalan_download_url' => $suratJalanPengiriman?->download_url,
            'surat_jalan_original_name' => $suratJalanPengiriman?->original_name,
            'surat_jalan_type' => $suratJalanPengiriman?->jenis,
            'pengembali_nama' => $suratJalanPengembalian?->pengirim_nama,
            'surat_jalan_pengembalian_path' => $suratJalanPengembalian?->path,
            'surat_jalan_pengembalian_url' => $suratJalanPengembalian?->download_url,
            'surat_jalan_pengembalian_download_url' => $suratJalanPengembalian?->download_url,
            'surat_jalan_pengembalian_original_name' => $suratJalanPengembalian?->original_name,
            'surat_jalan_pengembalian_type' => $suratJalanPengembalian?->jenis,
            'surat_jalan_items' => $suratJalanItems,
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
            $periode = trim(($peminjaman->tanggal_pinjam?->format('d/m/Y') ?? '-').' - '.($peminjaman->tanggal_kembali?->format('d/m/Y') ?? '-'));
            $base = [
                'ID Peminjaman' => $peminjaman->id,
                'Pekerjaan' => $peminjaman->pekerjaan,
                'Tanggal Dibuat' => $createdAt,
                'Periode Pinjaman' => $periode,
                'Nama Peminjam' => $peminjaman->user?->name ?? '-',
                'Nama Reviewer' => $peminjaman->requesterReviewer?->name ?? $peminjaman->reviewer?->name ?? '-',
                'Disetujui' => $peminjaman->reviewer?->name ?? '-',
                'Area Dipinjam' => $peminjaman->area?->name ?? '-',
                'Kategori' => $peminjaman->kategori ?? Peminjaman::KATEGORI_INTRA_AREA,
                'Status' => $peminjaman->status,
            ];

            $tools = $peminjaman->items;
            if ($tools->isEmpty()) {
                return [[
                    ...$base,
                    'Nama Alat' => '-',
                    'Jumlah Diminta' => 0,
                    'Jumlah Disetujui' => 0,
                    'Jumlah Ditolak' => 0,
                    'Jumlah Telah Dikembalikan' => 0,
                ]];
            }

            return $tools->map(function (PeminjamanItem $item) use ($base) {
                $qty = (int) ($item->qty ?? 0);
                $approvedQty = (int) ($item->approved_qty ?? 0);
                $returnedQty = (int) ($item->returned_qty ?? 0);
                $rejectedQty = $item->review_status === 'Menunggu Review'
                    ? 0
                    : max($qty - $approvedQty, 0);

                return [
                    ...$base,
                    'Nama Alat' => $item->alat?->nama ?? '-',
                    'Jumlah Diminta' => $qty,
                    'Jumlah Disetujui' => $approvedQty,
                    'Jumlah Ditolak' => $rejectedQty,
                    'Jumlah Telah Dikembalikan' => $returnedQty,
                ];
            });
        })->values();

        $filename = 'riwayat-peminjaman-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($rows) {
            $handle = fopen('php://output', 'wb');
            fwrite($handle, "\xEF\xBB\xBF");
            fwrite($handle, "sep=;\n");

            fputcsv($handle, [
                'ID Peminjaman',
                'Pekerjaan',
                'Tanggal Dibuat',
                'Periode Pinjaman',
                'Nama Peminjam',
                'Nama Reviewer',
                'Disetujui',
                'Area Dipinjam',
                'Kategori',
                'Status',
                'Nama Alat',
                'Jumlah Diminta',
                'Jumlah Disetujui',
                'Jumlah Ditolak',
                'Jumlah Telah Dikembalikan',
            ], ';');

            foreach ($rows as $row) {
                fputcsv($handle, [
                    $row['ID Peminjaman'] ?? '',
                    $row['Pekerjaan'] ?? '',
                    $row['Tanggal Dibuat'] ?? '',
                    $row['Periode Pinjaman'] ?? '',
                    $row['Nama Peminjam'] ?? '',
                    $row['Nama Reviewer'] ?? '',
                    $row['Disetujui'] ?? '',
                    $row['Area Dipinjam'] ?? '',
                    $row['Kategori'] ?? '',
                    $row['Status'] ?? '',
                    $row['Nama Alat'] ?? '',
                    $row['Jumlah Diminta'] ?? '',
                    $row['Jumlah Disetujui'] ?? '',
                    $row['Jumlah Ditolak'] ?? '',
                    $row['Jumlah Telah Dikembalikan'] ?? '',
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
            'pekerjaan' => ['required', 'string', 'max:1000'],
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
                ->where(function ($query) use ($targetAreaId) {
                    $query
                        ->where('area_id', $targetAreaId)
                        ->orWhereHas('areaStocks', function ($stockQuery) use ($targetAreaId) {
                            $stockQuery
                                ->where('area_id', $targetAreaId)
                                ->where('active', true)
                                ->where('qty', '>', 0);
                        });
                })
                ->lockForUpdate()
                ->get();

            if ($alats->count() !== $items->count()) {
                throw ValidationException::withMessages([
                    'items' => ['Ada alat yang tidak ditemukan.'],
                ]);
            }

            $borrowedMap = $this->borrowedMapForArea($alats->pluck('id')->all(), (int) $targetAreaId);
            $areaStockMap = $this->areaStockMap((int) $targetAreaId, $alats->pluck('id')->all());
            $areaStocks = AreaAlatStock::query()
                ->with('sourcePeminjaman')
                ->where('area_id', $targetAreaId)
                ->where('active', true)
                ->where('qty', '>', 0)
                ->whereIn('alat_id', $alats->pluck('id')->all())
                ->get()
                ->keyBy('alat_id');

            foreach ($alats as $alat) {
                $qty = (int) $items[$alat->id]['qty'];
                if ($qty < 1) {
                    throw ValidationException::withMessages([
                        'items' => ['Jumlah alat harus lebih dari 0.'],
                    ]);
                }
                $borrowedQty = $borrowedMap[$alat->id] ?? 0;
                $baseStock = (int) $alat->area_id === (int) $targetAreaId
                    ? (int) $alat->total_aset
                    : (int) ($areaStockMap[$alat->id] ?? 0);
                if ((int) $alat->area_id !== (int) $targetAreaId) {
                    $stock = $areaStocks->get($alat->id);
                    $source = $stock?->sourcePeminjaman;
                    if (! $source
                        || $validated['tanggal_pinjam'] < $source->tanggal_pinjam->toDateString()
                        || $validated['tanggal_kembali'] > $source->tanggal_kembali->toDateString()
                    ) {
                        throw ValidationException::withMessages([
                            'tanggal_pinjam' => ["Tanggal peminjaman {$alat->nama} harus berada dalam periode peminjaman antar area."],
                        ]);
                    }
                }
                $available = max($baseStock - $borrowedQty, 0);
                if ($available < $qty) {
                    throw ValidationException::withMessages([
                        'items' => ["Stok {$alat->nama} tidak mencukupi."],
                    ]);
                }
            }

            $peminjaman = Peminjaman::create([
                'user_id' => $user->id,
                'area_id' => $targetAreaId,
                'status' => Peminjaman::STATUS_PERLU_DISETUJUI,
                'kategori' => Peminjaman::KATEGORI_INTRA_AREA,
                'tanggal_pinjam' => $validated['tanggal_pinjam'],
                'tanggal_kembali' => $validated['tanggal_kembali'],
                'pekerjaan' => $validated['pekerjaan'],
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

    public function update(Request $request, Peminjaman $peminjaman)
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $user->loadMissing('role');
        $roleKey = strtolower((string) ($user->role?->key ?? ''));
        if (! in_array($roleKey, [Role::KEY_ADMIN, Role::KEY_SUPER_ADMIN], true)) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }
        $this->ensureAdminCanAccessPeminjaman($request, $peminjaman);

        $validated = $request->validate([
            'pekerjaan' => ['required', 'string', 'max:1000'],
            'tanggal_pinjam' => ['required', 'date'],
            'tanggal_kembali' => ['required', 'date', 'after_or_equal:tanggal_pinjam'],
            'status' => ['required', 'in:'.implode(',', [
                Peminjaman::STATUS_PERLU_DISETUJUI,
                Peminjaman::STATUS_MENUNGGU_REVIEW_AREA_PEMINJAM,
                Peminjaman::STATUS_DISETUJUI,
                Peminjaman::STATUS_DIKIRIM,
                Peminjaman::STATUS_DITERIMA,
                Peminjaman::STATUS_DIKEMBALIKAN_PARTIALS,
                Peminjaman::STATUS_DIKEMBALIKAN_SEMUANYA,
                Peminjaman::STATUS_SELESAI,
                Peminjaman::STATUS_DITOLAK,
            ])],
        ]);

        $protectedWorkflowStatuses = [
            Peminjaman::STATUS_DIKIRIM,
            Peminjaman::STATUS_DITERIMA,
            Peminjaman::STATUS_DIKEMBALIKAN_PARTIALS,
            Peminjaman::STATUS_DIKEMBALIKAN_SEMUANYA,
            Peminjaman::STATUS_SELESAI,
        ];

        $peminjaman = DB::transaction(function () use ($peminjaman, $validated, $protectedWorkflowStatuses) {
            $lockedPeminjaman = Peminjaman::query()
                ->whereKey($peminjaman->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if (
                $validated['status'] !== $lockedPeminjaman->status
                && (
                    in_array($validated['status'], $protectedWorkflowStatuses, true)
                    || in_array($lockedPeminjaman->status, $protectedWorkflowStatuses, true)
                )
            ) {
                throw ValidationException::withMessages([
                    'status' => [
                        'Perubahan status pengiriman, penerimaan, pengembalian, atau selesai '
                        .'harus melalui alur operasional terkait.',
                    ],
                ]);
            }

            $lockedPeminjaman->update([
                'pekerjaan' => $validated['pekerjaan'],
                'tanggal_pinjam' => $validated['tanggal_pinjam'],
                'tanggal_kembali' => $validated['tanggal_kembali'],
                'status' => $validated['status'],
            ]);

            return $lockedPeminjaman;
        });

        $peminjaman->load(['items.alat.area', 'suratJalans.photos', 'user', 'reviewer', 'requesterReviewer', 'area', 'requesterArea']);

        return response()->json($this->transformPeminjaman($peminjaman));
    }

    public function destroy(Request $request, Peminjaman $peminjaman)
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $user->loadMissing('role');
        $roleKey = strtolower((string) ($user->role?->key ?? ''));
        if (! in_array($roleKey, [Role::KEY_ADMIN, Role::KEY_SUPER_ADMIN], true)) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }
        $this->ensureAdminCanAccessPeminjaman($request, $peminjaman);

        $this->deletePeminjamans(collect([$peminjaman]));

        return response()->json(['message' => 'Peminjaman berhasil dihapus.']);
    }

    public function destroyArea(Request $request)
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $user->loadMissing('role');
        $roleKey = strtolower((string) ($user->role?->key ?? ''));
        if ($roleKey !== Role::KEY_SUPER_ADMIN) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $validated = $request->validate([
            'area_id' => ['required', 'integer', 'exists:areas,id'],
        ]);

        $peminjamans = Peminjaman::query()
            ->with(['suratJalans.photos'])
            ->where('area_id', $validated['area_id'])
            ->get();

        $deleted = $peminjamans->count();
        $this->deletePeminjamans($peminjamans);

        return response()->json([
            'message' => 'Semua peminjaman pada area aktif berhasil dihapus.',
            'deleted' => $deleted,
        ]);
    }

    public function storeInterArea(Request $request)
    {
        $validated = $request->validate([
            'source_area_id' => ['required', 'integer', 'exists:areas,id'],
            'requester_area_id' => ['nullable', 'integer', 'exists:areas,id'],
            'tanggal_pinjam' => ['required', 'date'],
            'tanggal_kembali' => ['required', 'date', 'after_or_equal:tanggal_pinjam'],
            'pekerjaan' => ['required', 'string', 'max:1000'],
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
        $isPicTools = $roleKey === Role::KEY_PIC_TOOL;
        $isSuperAdmin = $roleKey === Role::KEY_SUPER_ADMIN;
        $isAdmin = $roleKey === Role::KEY_ADMIN || $isSuperAdmin;

        if (! $isPicTools && ! $isAdmin) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $requesterAreaId = $isSuperAdmin
            ? ($validated['requester_area_id'] ?? $user->area_id)
            : $user->area_id;
        $sourceAreaId = (int) $validated['source_area_id'];

        if (! $requesterAreaId) {
            throw ValidationException::withMessages([
                'requester_area_id' => ['Area peminjam wajib dipilih.'],
            ]);
        }

        if ((int) $requesterAreaId === $sourceAreaId) {
            throw ValidationException::withMessages([
                'source_area_id' => ['Pilih area sumber yang berbeda dari area peminjam.'],
            ]);
        }

        $items = collect($validated['items'])->keyBy('id');

        $peminjaman = DB::transaction(function () use ($items, $validated, $user, $sourceAreaId, $requesterAreaId) {
            $alats = Alat::query()
                ->whereIn('id', $items->keys()->all())
                ->where('area_id', $sourceAreaId)
                ->lockForUpdate()
                ->get();

            if ($alats->count() !== $items->count()) {
                throw ValidationException::withMessages([
                    'items' => ['Ada alat yang tidak ditemukan di area sumber.'],
                ]);
            }

            $borrowedMap = $this->borrowedMap($alats->pluck('id')->all());

            foreach ($alats as $alat) {
                $qty = (int) $items[$alat->id]['qty'];
                $available = max(((int) $alat->total_aset) - ($borrowedMap[$alat->id] ?? 0), 0);
                if ($available < $qty) {
                    throw ValidationException::withMessages([
                        'items' => ["Stok {$alat->nama} tidak mencukupi di area sumber."],
                    ]);
                }
            }

            $peminjaman = Peminjaman::create([
                'user_id' => $user->id,
                'area_id' => $sourceAreaId,
                'requester_area_id' => $requesterAreaId,
                'is_inter_area' => true,
                'status' => Peminjaman::STATUS_MENUNGGU_REVIEW_AREA_PEMINJAM,
                'kategori' => Peminjaman::KATEGORI_ANTAR_AREA,
                'tanggal_pinjam' => $validated['tanggal_pinjam'],
                'tanggal_kembali' => $validated['tanggal_kembali'],
                'pekerjaan' => $validated['pekerjaan'],
            ]);

            $now = now();
            $rows = $alats->map(fn (Alat $alat) => [
                'peminjaman_id' => $peminjaman->id,
                'alat_id' => $alat->id,
                'qty' => (int) $items[$alat->id]['qty'],
                'created_at' => $now,
                'updated_at' => $now,
            ])->values()->all();

            if ($rows) {
                PeminjamanItem::insert($rows);
            }

            return $peminjaman;
        });

        return response()->json([
            'id' => $peminjaman->id,
            'status' => $peminjaman->status,
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
                        WHEN pem.status IN ('".Peminjaman::STATUS_MENUNGGU_REVIEW."', '".Peminjaman::STATUS_MENUNGGU_REVIEW_AREA_PEMINJAM."') THEN items.qty
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
                        WHEN pem.status IN ('".Peminjaman::STATUS_MENUNGGU_REVIEW."', '".Peminjaman::STATUS_MENUNGGU_REVIEW_AREA_PEMINJAM."') THEN items.qty
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

    private function areaStockMap(int $areaId, array $alatIds): array
    {
        if (! $alatIds) {
            return [];
        }

        return AreaAlatStock::query()
            ->where('area_id', $areaId)
            ->where('active', true)
            ->whereIn('alat_id', $alatIds)
            ->select('alat_id', DB::raw('SUM(qty) as total'))
            ->groupBy('alat_id')
            ->pluck('total', 'alat_id')
            ->map(fn ($value) => (int) $value)
            ->all();
    }

    private function deletePeminjamans($peminjamans): void
    {
        if ($peminjamans->isEmpty()) {
            return;
        }

        $filesToDelete = DB::transaction(function () use ($peminjamans) {
            $ids = $peminjamans->pluck('id')->map(fn ($id) => (int) $id)->all();
            $logPeminjamans = Peminjaman::query()
                ->whereIn('id', $ids)
                ->orderBy('id')
                ->lockForUpdate()
                ->get();
            $logPeminjamans->load(['items', 'suratJalans.photos']);

            $filesToDelete = $logPeminjamans
                ->flatMap(fn (Peminjaman $peminjaman) => $peminjaman->suratJalans)
                ->flatMap(function (SuratJalan $document) {
                    $files = collect();

                    if ($document->path) {
                        $files->push([
                            'disk' => $document->disk ?: 'public',
                            'path' => $document->path,
                        ]);
                    }

                    return $files->concat(
                        $document->photos
                            ->filter(fn ($photo) => filled($photo->path))
                            ->map(fn ($photo) => [
                                'disk' => $photo->disk ?: 'local',
                                'path' => $photo->path,
                            ])
                    );
                })
                ->unique(fn (array $file) => $file['disk'].'|'.$file['path'])
                ->values()
                ->all();

            DB::table('peminjaman_items')->whereIn('peminjaman_id', $ids)->delete();
            DB::table('surat_jalan')->whereIn('peminjaman_id', $ids)->delete();
            AreaAlatStock::query()->whereIn('source_peminjaman_id', $ids)->delete();
            LaporanAlat::query()
                ->whereIn('source_peminjaman_id', $ids)
                ->update(['source_peminjaman_id' => null]);

            foreach ($logPeminjamans as $peminjaman) {
                ActivityLogger::log('delete', $peminjaman, [
                    'old_values' => $this->peminjamanLogSnapshot($peminjaman),
                    'properties' => [
                        'items_count' => $peminjaman->items->count(),
                        'surat_jalan_count' => $peminjaman->suratJalans->count(),
                    ],
                    'description' => "Menghapus data peminjaman #{$peminjaman->id} {$peminjaman->pekerjaan}.",
                ]);
            }

            Peminjaman::query()->whereIn('id', $ids)->delete();

            return $filesToDelete;
        });

        foreach ($filesToDelete as $file) {
            try {
                if (! Storage::disk($file['disk'])->delete($file['path'])) {
                    Log::warning('File peminjaman tidak dapat dihapus setelah transaksi database selesai.', $file);
                }
            } catch (Throwable $exception) {
                Log::warning('Terjadi kesalahan saat menghapus file peminjaman.', [
                    ...$file,
                    'error' => $exception->getMessage(),
                ]);
            }
        }
    }

    private function peminjamanLogSnapshot(Peminjaman $peminjaman): array
    {
        return [
            'id' => $peminjaman->id,
            'pekerjaan' => $peminjaman->pekerjaan,
            'status' => $peminjaman->status,
            'kategori' => $peminjaman->kategori,
            'user_id' => $peminjaman->user_id,
            'area_id' => $peminjaman->area_id,
            'requester_area_id' => $peminjaman->requester_area_id,
            'tanggal_pinjam' => $peminjaman->tanggal_pinjam?->toDateString(),
            'tanggal_kembali' => $peminjaman->tanggal_kembali?->toDateString(),
        ];
    }
}
