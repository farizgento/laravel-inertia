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

        return $peminjamans->map(function (Peminjaman $peminjaman) {
            $tools = $peminjaman->items->map(function (PeminjamanItem $item) {
                $alat = $item->alat;
                return [
                    'item_id' => $item->id,
                    'alat_id' => $item->alat_id,
                    'name' => $alat?->nama ?? '-',
                    'code' => $alat ? sprintf('ALT-%03d', $alat->id) : '-',
                    'qty' => (int) $item->qty,
                    'approved_qty' => (int) ($item->approved_qty ?? 0),
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
                ];
            })->values();

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
            ];
        })->values();
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

        if ($peminjaman->status !== 'Terkirim') {
            return response()->json(['message' => 'Peminjaman tidak dalam status Terkirim.'], 422);
        }

        $peminjaman->update([
            'status' => 'Diterima',
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

        if ($peminjaman->status !== 'Diterima') {
            return response()->json(['message' => 'Peminjaman tidak dalam status Diterima.'], 422);
        }

        $validated = $request->validate([
            'laporan' => ['nullable', 'array'],
            'laporan.kategori' => ['required_with:laporan', 'in:' . implode(',', [
                LaporanAlat::CATEGORY_KERUSAKAN,
                LaporanAlat::CATEGORY_KEHILANGAN,
            ])],
            'laporan.alat_id' => ['required_with:laporan', 'integer', 'exists:alats,id'],
            'laporan.deskripsi' => ['required_with:laporan', 'string', 'max:1000'],
            'laporan.jumlah' => ['required_with:laporan', 'integer', 'min:1'],
            'laporan.foto' => ['required_with:laporan', 'image', 'max:5120'],
        ]);

        DB::transaction(function () use ($peminjaman, $validated, $user) {
            $peminjaman->update([
                'status' => 'Dikembalikan',
            ]);

            if (! empty($validated['laporan'])) {
                $this->storeReturnReport($peminjaman, $validated['laporan'], $user->id);
            }
        });

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
            ->first(fn (PeminjamanItem $row) => (int) $row->alat_id === $alatId && (int) ($row->approved_qty ?? 0) > 0);

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

        if ($jumlah > (int) ($item->approved_qty ?? 0)) {
            throw ValidationException::withMessages([
                'laporan.jumlah' => ['Jumlah laporan melebihi jumlah alat yang dipinjam.'],
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
