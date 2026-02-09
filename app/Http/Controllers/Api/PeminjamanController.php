<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Alat;
use App\Models\Peminjaman;
use App\Models\PeminjamanItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PeminjamanController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $search = trim((string) $request->query('search', ''));
        $status = trim((string) $request->query('status', ''));

        $query = Peminjaman::query()
            ->with(['items.alat'])
            ->where('user_id', $user->id)
            ->orderByDesc('created_at');

        if ($search !== '') {
            $query->where(function ($sub) use ($search) {
                $sub->where('keperluan', 'like', '%' . $search . '%')
                    ->orWhere('id', $search);
            });
        }

        if ($status !== '' && $status !== 'Semua') {
            $query->where('status', $status);
        }

        $peminjamans = $query->get();

        return $peminjamans->map(function (Peminjaman $peminjaman) {
            $tools = $peminjaman->items->map(function (PeminjamanItem $item) {
                $alat = $item->alat;
                return [
                    'name' => $alat?->nama ?? '-',
                    'code' => $alat ? sprintf('ALT-%03d', $alat->id) : '-',
                    'qty' => (int) $item->qty,
                    'approved_qty' => (int) ($item->approved_qty ?? 0),
                    'review_status' => $item->review_status ?? 'Menunggu Review',
                    'rejection_reason' => $item->rejection_reason,
                ];
            })->values();

            return [
                'id' => $peminjaman->id,
                'title' => $peminjaman->keperluan,
                'created_at' => $peminjaman->created_at
                    ? $peminjaman->created_at->format('d M Y H:i')
                    : null,
                'return_date' => $peminjaman->tanggal_kembali
                    ? $peminjaman->tanggal_kembali->format('d M Y')
                    : null,
                'item_count' => $peminjaman->items->sum('qty'),
                'status' => $peminjaman->status,
                'tools' => $tools,
            ];
        })->values();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'tanggal_pinjam' => ['required', 'date', 'after_or_equal:today'],
            'tanggal_kembali' => ['required', 'date', 'after_or_equal:tanggal_pinjam'],
            'keperluan' => ['required', 'string', 'max:1000'],
            'catatan' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.id' => ['required', 'integer'],
            'items.*.qty' => ['required', 'integer', 'min:1'],
        ]);

        $user = $request->user();
        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $items = collect($validated['items'])->keyBy('id');

        $peminjaman = DB::transaction(function () use ($items, $validated, $user) {
            $alats = Alat::query()
                ->whereIn('id', $items->keys()->all())
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
                'area_id' => $user->area_id,
                'status' => 'Menunggu Review',
                'tanggal_pinjam' => $validated['tanggal_pinjam'],
                'tanggal_kembali' => $validated['tanggal_kembali'],
                'keperluan' => $validated['keperluan'],
                'catatan' => $validated['catatan'] ?? null,
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
            ->whereIn('pem.status', ['Menunggu Review', 'Diproses', 'Terkirim'])
            ->groupBy('items.alat_id')
            ->select(
                'items.alat_id',
                DB::raw(
                    "SUM(CASE
                        WHEN pem.status = 'Menunggu Review' THEN items.qty
                        WHEN pem.status IN ('Diproses', 'Terkirim') THEN COALESCE(items.approved_qty, 0)
                        ELSE 0
                    END) as total"
                )
            )
            ->pluck('total', 'alat_id')
            ->map(fn ($value) => (int) $value)
            ->all();
    }
}
