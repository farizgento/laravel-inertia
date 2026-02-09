<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Peminjaman;
use App\Models\PeminjamanItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReviewPeminjamanController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $user->loadMissing('role');
        $roleKey = strtolower((string) ($user->role?->key ?? ''));
        if ($roleKey !== 'sp_tool') {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        if (! $user->area_id) {
            return response()->json([]);
        }

        $search = trim((string) $request->query('search', ''));
        $status = trim((string) $request->query('status', ''));

        $query = Peminjaman::query()
            ->with(['items.alat', 'user'])
            ->where('area_id', $user->area_id)
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
                    'item_id' => $item->id,
                    'alat_id' => $item->alat_id,
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
                'user_name' => $peminjaman->user?->name ?? '-',
                'review_note' => $peminjaman->review_note,
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

    public function review(Request $request, Peminjaman $peminjaman)
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $user->loadMissing('role');
        $roleKey = strtolower((string) ($user->role?->key ?? ''));
        if ($roleKey !== 'sp_tool') {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        if (! $user->area_id || $peminjaman->area_id !== $user->area_id) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $validated = $request->validate([
            'review_note' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.item_id' => ['required', 'integer'],
            'items.*.decision' => ['required', 'in:setujui,tolak'],
            'items.*.approved_qty' => ['required', 'integer', 'min:0'],
            'items.*.rejection_reason' => ['nullable', 'string', 'max:500'],
        ]);

        $items = collect($validated['items'])->keyBy('item_id');

        $peminjaman->loadMissing('items');
        $itemModels = $peminjaman->items->keyBy('id');

        if ($items->count() !== $itemModels->count()) {
            throw ValidationException::withMessages([
                'items' => ['Semua item peminjaman harus direview.'],
            ]);
        }

        foreach ($items as $itemId => $payload) {
            if (! $itemModels->has($itemId)) {
                throw ValidationException::withMessages([
                    'items' => ['Item tidak ditemukan dalam peminjaman ini.'],
                ]);
            }
        }

        $hasApproved = false;

        DB::transaction(function () use ($items, $itemModels, $peminjaman, &$hasApproved, $validated) {
            foreach ($items as $itemId => $payload) {
                $item = $itemModels[$itemId];
                $decision = $payload['decision'];
                $requestedQty = (int) $item->qty;

                $approvedQty = $decision === 'tolak'
                    ? 0
                    : min((int) $payload['approved_qty'], $requestedQty);

                if ($approvedQty > 0) {
                    $hasApproved = true;
                }

                $item->update([
                    'approved_qty' => $approvedQty,
                    'review_status' => $decision === 'tolak' ? 'Ditolak' : 'Disetujui',
                    'rejection_reason' => $decision === 'tolak'
                        ? ($payload['rejection_reason'] ?? null)
                        : null,
                ]);
            }

            $peminjaman->update([
                'review_note' => $validated['review_note'] ?? null,
                'reviewed_at' => now(),
                'status' => $hasApproved ? 'Diproses' : 'Ditolak',
            ]);
        });

        return response()->json([
            'id' => $peminjaman->id,
            'status' => $peminjaman->status,
        ]);
    }
}
