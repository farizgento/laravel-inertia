<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Alat;
use App\Models\LaporanAlat;
use App\Models\Peminjaman;
use App\Models\PeminjamanItem;
use App\Models\PeminjamanItemPhoto;
use App\Models\Role;
use App\Models\SuratJalan;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;
use Intervention\Image\Drivers\Imagick\Driver as ImagickDriver;
use Intervention\Image\ImageManager;

class PengirimanController extends Controller
{
    private function formatPeminjaman(Peminjaman $peminjaman): array
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
                'item_id' => $item->id,
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
                    ? $item->photos->map(fn (PeminjamanItemPhoto $photo) => [
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
            'area_name' => $peminjaman->area?->name ?? 'Area tidak diketahui',
            'area_id' => $peminjaman->area_id,
            'created_at' => $peminjaman->created_at
                ? $peminjaman->created_at->format('d M Y H:i')
                : null,
            'borrow_date' => $peminjaman->tanggal_pinjam
                ? $peminjaman->tanggal_pinjam->format('d M Y')
                : null,
            'return_date' => $peminjaman->tanggal_kembali
                ? $peminjaman->tanggal_kembali->format('d M Y')
                : null,
            'item_count' => $peminjaman->items->sum('approved_qty'),
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

        $user->loadMissing('role');
        $roleKey = strtolower((string) ($user->role?->key ?? ''));
        $isPicTools = in_array($roleKey, [Role::KEY_PIC_TOOLS, 'pic_tool'], true);
        $isAdmin = in_array($roleKey, [Role::KEY_ADMIN, Role::KEY_SUPER_ADMIN], true);

        if (! $isPicTools && ! $isAdmin) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $search = trim((string) $request->query('search', ''));
        $areaIdParam = $request->query('area_id');

        $query = Peminjaman::query()
            ->with([
                'items' => function ($sub) {
                    $sub->where('approved_qty', '>', 0);
                },
                'items.alat',
                'items.photos',
                'suratJalan',
                'area',
                'user',
            ])
            ->whereIn('status', ['Dipesan', 'Disiapkan', 'Terkirim'])
            ->whereHas('items', function ($sub) {
                $sub->where('approved_qty', '>', 0);
            })
            ->orderByDesc('created_at');

        if ($isAdmin) {
            if (! empty($areaIdParam)) {
                $query->where('area_id', $areaIdParam);
            }
        } else {
            if (! $user->area_id) {
                return response()->json([]);
            }

            $query->where('area_id', $user->area_id);
        }

        if ($search !== '') {
            $query->where(function ($sub) use ($search) {
                $sub->where('keperluan', 'like', '%' . $search . '%')
                    ->orWhere('id', $search);
            });
        }

        $peminjamans = $query->get();

        return $peminjamans->map(fn (Peminjaman $peminjaman) => $this->formatPeminjaman($peminjaman))->values();
    }

    public function pengembalianIndex(Request $request)
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $user->loadMissing('role');
        $roleKey = strtolower((string) ($user->role?->key ?? ''));
        $isPicTools = in_array($roleKey, [Role::KEY_PIC_TOOLS, 'pic_tool'], true);
        $isAdmin = in_array($roleKey, [Role::KEY_ADMIN, Role::KEY_SUPER_ADMIN], true);

        if (! $isPicTools && ! $isAdmin) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $search = trim((string) $request->query('search', ''));
        $areaIdParam = $request->query('area_id');

        $query = Peminjaman::query()
            ->with([
                'items' => function ($sub) {
                    $sub->where('approved_qty', '>', 0);
                },
                'items.alat',
                'items.photos',
                'suratJalan',
                'area',
                'user',
            ])
            ->whereIn('status', [
                Peminjaman::STATUS_DIKEMBALIKAN_PARTIALS,
                Peminjaman::STATUS_DIKEMBALIKAN_SEMUANYA,
            ])
            ->whereHas('items', function ($sub) {
                $sub->where('approved_qty', '>', 0);
            })
            ->orderByDesc('updated_at');

        if ($isAdmin) {
            if (! empty($areaIdParam)) {
                $query->where('area_id', $areaIdParam);
            }
        } else {
            if (! $user->area_id) {
                return response()->json([]);
            }

            $query->where('area_id', $user->area_id);
        }

        if ($search !== '') {
            $query->where(function ($sub) use ($search) {
                $sub->where('keperluan', 'like', '%' . $search . '%')
                    ->orWhere('id', $search);
            });
        }

        return $query->get()->map(fn (Peminjaman $peminjaman) => $this->formatPeminjaman($peminjaman))->values();
    }

    public function siapkan(Request $request, Peminjaman $peminjaman)
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $user->loadMissing('role');
        $roleKey = strtolower((string) ($user->role?->key ?? ''));
        $isPicTools = in_array($roleKey, [Role::KEY_PIC_TOOLS, 'pic_tool'], true);
        $isAdmin = in_array($roleKey, [Role::KEY_ADMIN, Role::KEY_SUPER_ADMIN], true);

        if (! $isPicTools && ! $isAdmin) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        if ($isPicTools && (! $user->area_id || $peminjaman->area_id !== $user->area_id)) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        if ($peminjaman->status !== 'Dipesan') {
            return response()->json(['message' => 'Peminjaman tidak dalam status Dipesan.'], 422);
        }

        $validated = $request->validate([
            'items' => ['nullable', 'array'],
            'items.*.item_id' => ['required', 'integer'],
            'items.*.photos' => ['required', 'array', 'min:1'],
            'items.*.photos.*' => ['required', 'image', 'max:5120'],
        ]);

        $submittedItems = collect($validated['items'] ?? [])->keyBy('item_id');

        $peminjaman->loadMissing('items');
        $itemModels = $peminjaman->items->keyBy('id');
        $approvedItems = $itemModels->filter(
            fn (PeminjamanItem $item) => (int) ($item->approved_qty ?? 0) > 0
        );

        if ($approvedItems->isEmpty()) {
            throw ValidationException::withMessages([
                'items' => ['Tidak ada item yang disetujui untuk disiapkan.'],
            ]);
        }

        foreach ($submittedItems as $itemId => $payload) {
            if (! $itemModels->has($itemId)) {
                throw ValidationException::withMessages([
                    'items' => ['Item tidak ditemukan dalam peminjaman ini.'],
                ]);
            }

            if (! $approvedItems->has($itemId)) {
                throw ValidationException::withMessages([
                    'items' => ['Item belum disetujui sehingga tidak perlu diunggah.'],
                ]);
            }
        }

        $missingApproved = $approvedItems->keys()->diff($submittedItems->keys());
        if ($missingApproved->isNotEmpty()) {
            throw ValidationException::withMessages([
                'items' => ['Semua item yang disetujui harus diunggah fotonya.'],
            ]);
        }

        DB::transaction(function () use ($submittedItems, $itemModels, $peminjaman) {
            foreach ($submittedItems as $itemId => $payload) {
                $item = $itemModels[$itemId];
                $photos = $payload['photos'] ?? [];

                foreach ($photos as $photo) {
                    if (! $photo instanceof UploadedFile) {
                        continue;
                    }

                    $stored = $this->storeCompressedPhoto($photo, "pengiriman/{$peminjaman->id}");

                    PeminjamanItemPhoto::create([
                        'peminjaman_item_id' => $item->id,
                        'path' => $stored['path'],
                        'original_name' => $photo->getClientOriginalName(),
                        'mime' => $stored['mime'],
                        'size' => $stored['size'],
                    ]);
                }
            }

            $peminjaman->update([
                'status' => 'Disiapkan',
            ]);
        });

        return response()->json([
            'id' => $peminjaman->id,
            'status' => $peminjaman->status,
        ]);
    }

    public function kirim(Request $request, Peminjaman $peminjaman)
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $user->loadMissing('role');
        $roleKey = strtolower((string) ($user->role?->key ?? ''));
        $isPicTools = in_array($roleKey, [Role::KEY_PIC_TOOLS, 'pic_tool'], true);
        $isAdmin = in_array($roleKey, [Role::KEY_ADMIN, Role::KEY_SUPER_ADMIN], true);

        if (! $isPicTools && ! $isAdmin) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        if ($isPicTools && (! $user->area_id || $peminjaman->area_id !== $user->area_id)) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        if ($peminjaman->status !== 'Disiapkan') {
            return response()->json(['message' => 'Peminjaman tidak dalam status Disiapkan.'], 422);
        }

        $validated = $request->validate([
            'pengirim_nama' => ['required', 'string', 'max:255'],
            'surat_jalan' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ]);

        $file = $validated['surat_jalan'];
        if (! $file instanceof UploadedFile) {
            throw ValidationException::withMessages([
                'surat_jalan' => ['Surat jalan tidak valid.'],
            ]);
        }

        DB::transaction(function () use ($validated, $file, $peminjaman) {
            $disk = Storage::disk('public');
            $dir = "surat-jalan/{$peminjaman->id}";
            $extension = $file->getClientOriginalExtension() ?: 'pdf';
            $filename = Str::uuid()->toString() . '.' . $extension;
            $path = $file->storeAs($dir, $filename, 'public');

            $existing = $peminjaman->suratJalan;
            if ($existing && $existing->path) {
                $disk->delete($existing->path);
            }

            SuratJalan::updateOrCreate(
                ['peminjaman_id' => $peminjaman->id],
                [
                    'pengirim_nama' => $validated['pengirim_nama'],
                    'path' => $path,
                    'original_name' => $file->getClientOriginalName(),
                    'mime' => $file->getClientMimeType(),
                    'size' => $file->getSize(),
                ]
            );

            $peminjaman->update([
                'status' => 'Terkirim',
            ]);
        });

        return response()->json([
            'id' => $peminjaman->id,
            'status' => $peminjaman->status,
        ]);
    }

    public function terima(Request $request, Peminjaman $peminjaman)
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $user->loadMissing('role');
        $roleKey = strtolower((string) ($user->role?->key ?? ''));
        $isPicTools = in_array($roleKey, [Role::KEY_PIC_TOOLS, 'pic_tool'], true);
        $isUser = $roleKey === 'user';
        $isAdmin = in_array($roleKey, [Role::KEY_ADMIN, Role::KEY_SUPER_ADMIN], true);

        if (! $isPicTools && ! $isUser && ! $isAdmin) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        if ($isPicTools) {
            if (! $user->area_id || $peminjaman->area_id !== $user->area_id) {
                return response()->json(['message' => 'Forbidden.'], 403);
            }
        } elseif (! $isAdmin && (string) $peminjaman->user_id !== (string) $user->id) {
            return response()->json(['message' => 'user id tidak sama.'], 403);
        }

        if ($peminjaman->status !== Peminjaman::STATUS_TERKIRIM) {
            return response()->json(['message' => 'Peminjaman tidak dalam status Terkirim.'], 422);
        }

        $peminjaman->update([
            'status' => Peminjaman::STATUS_DITERIMA,
        ]);

        return response()->json([
            'id' => $peminjaman->id,
            'status' => $peminjaman->status,
        ]);
    }

    public function kembalikan(Request $request, Peminjaman $peminjaman)
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $user->loadMissing('role');
        $roleKey = strtolower((string) ($user->role?->key ?? ''));
        if ($roleKey !== 'user') {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        if ((string) $peminjaman->user_id !== (string) $user->id) {
            return response()->json(['message' => 'user id tidak sama.'], 403);
        }

        if (! in_array($peminjaman->status, Peminjaman::returnableStatuses(), true)) {
            return response()->json(['message' => 'Peminjaman tidak dapat dikembalikan pada status saat ini.'], 422);
        }

        $validated = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.item_id' => ['required', 'integer'],
            'items.*.returned_qty' => ['required', 'integer', 'min:1'],
            'laporan' => ['nullable', 'array'],
            'laporan.*.kategori' => ['nullable', 'in:' . implode(',', [
                LaporanAlat::CATEGORY_KERUSAKAN,
                LaporanAlat::CATEGORY_KEHILANGAN,
            ])],
            'laporan.*.alat_id' => ['nullable', 'integer', 'exists:alats,id'],
            'laporan.*.deskripsi' => ['nullable', 'string', 'max:1000'],
            'laporan.*.jumlah' => ['nullable', 'integer', 'min:1'],
            'laporan.*.foto' => ['nullable', 'image', 'max:5120'],
        ]);

        $peminjaman->loadMissing('items');
        $itemModels = $peminjaman->items->keyBy('id');
        $submittedItems = collect($validated['items'])->keyBy('item_id');
        $returnedThisRequest = [];

        foreach ($submittedItems as $itemId => $payload) {
            if (! $itemModels->has($itemId)) {
                throw ValidationException::withMessages([
                    'items' => ['Item tidak ditemukan dalam peminjaman ini.'],
                ]);
            }

            $item = $itemModels[$itemId];
            $remainingQty = max((int) ($item->approved_qty ?? 0) - (int) ($item->returned_qty ?? 0), 0);
            if ($remainingQty < 1) {
                throw ValidationException::withMessages([
                    'items' => ['Ada item yang sudah dikembalikan seluruhnya.'],
                ]);
            }

            $returnedQty = (int) ($payload['returned_qty'] ?? 0);
            if ($returnedQty > $remainingQty) {
                throw ValidationException::withMessages([
                    'items' => ["Jumlah pengembalian {$item->alat?->nama} melebihi sisa yang belum kembali."],
                ]);
            }

            $returnedThisRequest[(int) $item->alat_id] = $returnedQty;
        }

        $laporans = collect($validated['laporan'] ?? [])
            ->filter(function (array $laporan) {
                return collect($laporan)->filter(function ($value) {
                    if ($value instanceof UploadedFile) {
                        return true;
                    }

                    return $value !== null && $value !== '';
                })->isNotEmpty();
            })
            ->values();

        foreach ($laporans as $index => $laporan) {
            $requiredFields = ['kategori', 'alat_id', 'jumlah', 'deskripsi', 'foto'];
            foreach ($requiredFields as $field) {
                $value = $laporan[$field] ?? null;
                if ($value === null || $value === '') {
                    throw ValidationException::withMessages([
                        "laporan.$index.$field" => ['Lengkapi semua field laporan alat yang diisi.'],
                    ]);
                }
            }

            $laporanAlatId = (int) ($laporan['alat_id'] ?? 0);
            $laporanJumlah = (int) ($laporan['jumlah'] ?? 0);
            if (! isset($returnedThisRequest[$laporanAlatId])) {
                throw ValidationException::withMessages([
                    "laporan.$index.alat_id" => ['Laporan hanya bisa dibuat untuk alat yang sedang dikembalikan pada transaksi ini.'],
                ]);
            }

            if ($laporanJumlah > $returnedThisRequest[$laporanAlatId]) {
                throw ValidationException::withMessages([
                    "laporan.$index.jumlah" => ['Jumlah laporan melebihi jumlah alat yang dikembalikan pada transaksi ini.'],
                ]);
            }
        }

        DB::transaction(function () use ($peminjaman, $user, $submittedItems, $itemModels, $laporans) {
            foreach ($submittedItems as $itemId => $payload) {
                $item = $itemModels[$itemId];
                $item->update([
                    'returned_qty' => (int) ($item->returned_qty ?? 0) + (int) ($payload['returned_qty'] ?? 0),
                ]);
            }

            $peminjaman->refresh();
            $peminjaman->load('items');
            $peminjaman->update([
                'status' => $peminjaman->determineReturnStatus(),
            ]);

            foreach ($laporans as $laporan) {
                $this->storeReturnReport($peminjaman, $laporan, $user->id);
            }
        });

        return response()->json([
            'id' => $peminjaman->id,
            'status' => $peminjaman->status,
        ]);
    }

    public function selesai(Request $request, Peminjaman $peminjaman)
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $user->loadMissing('role');
        $roleKey = strtolower((string) ($user->role?->key ?? ''));
        $isPicTools = in_array($roleKey, [Role::KEY_PIC_TOOLS, 'pic_tool'], true);
        $isAdmin = in_array($roleKey, [Role::KEY_ADMIN, Role::KEY_SUPER_ADMIN], true);

        if (! $isPicTools && ! $isAdmin) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        if ($isPicTools && (! $user->area_id || (int) $peminjaman->area_id !== (int) $user->area_id)) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        if ($peminjaman->status !== Peminjaman::STATUS_DIKEMBALIKAN_SEMUANYA) {
            return response()->json(['message' => 'Peminjaman belum siap diselesaikan.'], 422);
        }

        $peminjaman->update([
            'status' => Peminjaman::STATUS_SELESAI,
        ]);

        return response()->json([
            'id' => $peminjaman->id,
            'status' => $peminjaman->status,
        ]);
    }

    private function storeReturnReport(Peminjaman $peminjaman, array $payload, int $userId): void
    {
        $alatId = (int) ($payload['alat_id'] ?? 0);
        $jumlah = (int) ($payload['jumlah'] ?? 0);

        $peminjaman->loadMissing('items');
        $item = $peminjaman->items
            ->first(
                fn (PeminjamanItem $row) => (int) $row->alat_id === $alatId
                    && (int) ($row->approved_qty ?? 0) > 0
                    && (int) ($row->returned_qty ?? 0) > 0
            );

        if (! $item) {
            throw ValidationException::withMessages([
                'laporan.alat_id' => ['Alat tidak ditemukan dalam peminjaman ini.'],
            ]);
        }

        $alat = Alat::query()->find($alatId);
        if (! $alat) {
            throw ValidationException::withMessages([
                'laporan.alat_id' => ['Alat tidak ditemukan.'],
            ]);
        }

        if ($jumlah > (int) ($item->returned_qty ?? 0)) {
            throw ValidationException::withMessages([
                'laporan.jumlah' => ['Jumlah laporan melebihi jumlah alat yang sudah dikembalikan.'],
            ]);
        }

        $file = $payload['foto'] ?? null;
        if (! $file instanceof UploadedFile) {
            throw ValidationException::withMessages([
                'laporan.foto' => ['Foto laporan alat tidak valid.'],
            ]);
        }

        $stored = $this->storeCompressedPhoto($file, "{$payload['kategori']}/{$alat->id}");

        LaporanAlat::create([
            'kategori' => $payload['kategori'],
            'deskripsi' => $payload['deskripsi'],
            'status' => 'Dilaporkan',
            'jumlah' => $jumlah,
            'alat_id' => $alat->id,
            'user_id' => $userId,
            'path' => $stored['path'],
            'original_name' => $file->getClientOriginalName(),
            'mime' => $stored['mime'],
            'size' => $stored['size'],
        ]);
    }

    private function storeCompressedPhoto(UploadedFile $file, string $dir): array
    {
        $disk = Storage::disk('public');
        $originalMime = $file->getClientMimeType();

        $driver = null;
        if (extension_loaded('imagick')) {
            $driver = new ImagickDriver();
        } elseif (extension_loaded('gd')) {
            $driver = new GdDriver();
        }

        if (! $driver) {
            $path = $file->store($dir, 'public');
            return [
                'path' => $path,
                'mime' => $originalMime,
                'size' => $file->getSize(),
            ];
        }

        try {
            $manager = new ImageManager($driver);
            $image = $manager->read($file->getRealPath())
                ->orient()
                ->scaleDown(1600, 1600);

            $image = $image->resizeCanvas($image->width(), $image->height(), 'ffffff');
            $encoded = $image->toJpeg(quality: 75);

            $filename = Str::uuid()->toString() . '.jpg';
            $path = $dir . '/' . $filename;
            $disk->put($path, (string) $encoded);

            return [
                'path' => $path,
                'mime' => 'image/jpeg',
                'size' => $encoded->size(),
            ];
        } catch (\Throwable $e) {
            $path = $file->store($dir, 'public');
            return [
                'path' => $path,
                'mime' => $originalMime,
                'size' => $file->getSize(),
            ];
        }
    }
}
