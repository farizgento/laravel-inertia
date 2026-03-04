<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\Peminjaman;
use App\Models\PeminjamanItem;
use App\Models\PeminjamanItemPhoto;
use Illuminate\Http\Request;

class MutasiAlatController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $user->loadMissing('role');
        $roleKey = strtolower((string) ($user->role?->key ?? ''));
        $isPicTools = in_array($roleKey, ['pic_tools', 'pic_tool'], true);
        $isUser = $roleKey === 'user';

        if (! $isPicTools && ! $isUser) {
            return response()->json(['message' => 'Forbidden.'], 403);
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
            ->whereIn('status', ['Terkirim', 'Diterima', 'Dikembalikan'])
            ->whereHas('items', function ($sub) {
                $sub->where('approved_qty', '>', 0);
            })
            ->orderByDesc('created_at');

        if ($isPicTools) {
            if (! $user->area_id) {
                return response()->json([]);
            }
            $query->where('area_id', $user->area_id);
        } else {
            $query->where('user_id', $user->id);
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
}
