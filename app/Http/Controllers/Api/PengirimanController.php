<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Peminjaman;
use App\Models\PeminjamanItem;
use App\Models\PeminjamanItemPhoto;
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
        if (! in_array($roleKey, ['pic_tools', 'pic_tool'], true)) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        if (! $user->area_id) {
            return response()->json([]);
        }

        $search = trim((string) $request->query('search', ''));

        $query = Peminjaman::query()
            ->with([
                'items' => function ($sub) {
                    $sub->where('approved_qty', '>', 0);
                },
                'items.alat',
                'items.photos',
                'suratJalan',
                'user',
            ])
            ->where('area_id', $user->area_id)
            ->whereIn('status', ['Dipesan', 'Disiapkan', 'Terkirim'])
            ->whereHas('items', function ($sub) {
                $sub->where('approved_qty', '>', 0);
            })
            ->orderByDesc('created_at');

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
        if (! in_array($roleKey, ['pic_tools', 'pic_tool'], true)) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        if (! $user->area_id || $peminjaman->area_id !== $user->area_id) {
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
        if (! in_array($roleKey, ['pic_tools', 'pic_tool'], true)) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        if (! $user->area_id || $peminjaman->area_id !== $user->area_id) {
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
