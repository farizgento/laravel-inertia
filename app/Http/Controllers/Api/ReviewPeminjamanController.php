<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Peminjaman;
use App\Models\PeminjamanItem;
use App\Models\Role;
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
        $isSpTool = $roleKey === Role::KEY_SP_TOOL;
        $isMgrTool = $roleKey === Role::KEY_MGR_TOOL;
        $isSuperAdmin = $roleKey === Role::KEY_SUPER_ADMIN;

        if (! $isSpTool && ! $isMgrTool && ! $isSuperAdmin) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $areaId = $isSuperAdmin && $request->filled('area_id')
            ? (int) $request->query('area_id')
            : $user->area_id;

        if (! $areaId) {
            return response()->json([]);
        }

        $search = trim((string) $request->query('search', ''));
        $status = trim((string) $request->query('status', ''));

        $query = Peminjaman::query()
            ->with(['items.alat.area', 'user', 'area', 'requesterArea', 'reviewer', 'requesterReviewer'])
            ->where(function ($sub) use ($areaId) {
                $sub->where(function ($sourceQuery) use ($areaId) {
                    $sourceQuery
                        ->where('area_id', $areaId)
                        ->where('status', Peminjaman::STATUS_MENUNGGU_REVIEW);
                })->orWhere(function ($requesterQuery) use ($areaId) {
                    $requesterQuery
                        ->where('is_inter_area', true)
                        ->where('requester_area_id', $areaId)
                        ->where('status', Peminjaman::STATUS_MENUNGGU_REVIEW_AREA_PEMINJAM);
                });
            })
            ->orderByDesc('created_at');

        if ($search !== '') {
            $query->where(function ($sub) use ($search) {
                $sub->where('pekerjaan', 'like', '%' . $search . '%')
                    ->orWhere('id', $search);
            });
        }

        if ($status !== '' && $status !== 'Semua') {
            $query->where('status', $status);
        }

        $peminjamans = $query->get();

        return $peminjamans->map(function (Peminjaman $peminjaman) use ($areaId) {
            $tools = $peminjaman->items->map(function (PeminjamanItem $item) use ($peminjaman, $areaId) {
                $alat = $item->alat;
                $qty = (int) $item->qty;
                if (
                    $peminjaman->is_inter_area
                    && $peminjaman->status === Peminjaman::STATUS_MENUNGGU_REVIEW
                    && (int) $peminjaman->area_id === (int) $areaId
                ) {
                    $qty = (int) ($item->approved_qty ?? 0);
                }

                return [
                    'item_id' => $item->id,
                    'alat_id' => $item->alat_id,
                    'name' => $alat?->nama ?? '-',
                    'code' => $alat?->kode ?? '-',
                    'qty' => $qty,
                    'approved_qty' => (int) ($item->approved_qty ?? 0),
                    'review_status' => $item->review_status ?? 'Menunggu Review',
                    'rejection_reason' => $item->rejection_reason,
                ];
            })->values();

            return [
                'id' => $peminjaman->id,
                'area_id' => $peminjaman->area_id,
                'area_name' => $peminjaman->area?->name ?? '-',
                'requester_area_id' => $peminjaman->requester_area_id,
                'requester_area_name' => $peminjaman->requesterArea?->name ?? '-',
                'is_inter_area' => (bool) $peminjaman->is_inter_area,
                'title' => $peminjaman->pekerjaan,
                'user_name' => $peminjaman->user?->name ?? '-',
                'review_note' => $peminjaman->review_note,
                'reviewed_by_name' => $peminjaman->reviewer?->name,
                'requester_review_note' => $peminjaman->requester_review_note,
                'requester_reviewed_by_name' => $peminjaman->requesterReviewer?->name,
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
                'kategori' => $peminjaman->kategori ?? Peminjaman::KATEGORI_INTRA_AREA,
                'tools' => $tools,
            ];
        })->values();
    }

    public function pendingCount(Request $request)
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $user->loadMissing('role');
        $roleKey = strtolower((string) ($user->role?->key ?? ''));
        $isSpTool = $roleKey === Role::KEY_SP_TOOL;
        $isMgrTool = $roleKey === Role::KEY_MGR_TOOL;
        $isSuperAdmin = $roleKey === Role::KEY_SUPER_ADMIN;

        if (! $isSpTool && ! $isMgrTool && ! $isSuperAdmin) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $areaId = $isSuperAdmin && $request->filled('area_id')
            ? (int) $request->query('area_id')
            : $user->area_id;

        if (! $areaId) {
            return response()->json(['count' => 0]);
        }

        return response()->json([
            'count' => Peminjaman::query()
                ->where(function ($sub) use ($areaId) {
                    $sub->where(function ($sourceQuery) use ($areaId) {
                        $sourceQuery
                            ->where('area_id', $areaId)
                            ->where('status', Peminjaman::STATUS_MENUNGGU_REVIEW);
                    })->orWhere(function ($requesterQuery) use ($areaId) {
                        $requesterQuery
                            ->where('is_inter_area', true)
                            ->where('requester_area_id', $areaId)
                            ->where('status', Peminjaman::STATUS_MENUNGGU_REVIEW_AREA_PEMINJAM);
                    });
                })
                ->count(),
        ]);
    }

    public function review(Request $request, Peminjaman $peminjaman)
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $user->loadMissing('role');
        $roleKey = strtolower((string) ($user->role?->key ?? ''));
        $isSpTool = $roleKey === Role::KEY_SP_TOOL;
        $isMgrTool = $roleKey === Role::KEY_MGR_TOOL;
        $isSuperAdmin = $roleKey === Role::KEY_SUPER_ADMIN;

        if (! $isSpTool && ! $isMgrTool && ! $isSuperAdmin) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $areaId = $isSuperAdmin && $request->filled('area_id')
            ? (int) $request->input('area_id')
            : $user->area_id;

        $isRequesterReviewStage = $peminjaman->is_inter_area
            && $peminjaman->status === Peminjaman::STATUS_MENUNGGU_REVIEW_AREA_PEMINJAM;
        $isSourceReviewStage = $peminjaman->status === Peminjaman::STATUS_MENUNGGU_REVIEW;

        $expectedAreaId = $isRequesterReviewStage
            ? $peminjaman->requester_area_id
            : $peminjaman->area_id;

        if (! $areaId || (int) $expectedAreaId !== (int) $areaId) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        if (! $isRequesterReviewStage && ! $isSourceReviewStage) {
            return response()->json([
                'message' => 'Peminjaman ini sudah direview dan hanya dapat dilihat detailnya.',
            ], 422);
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

        DB::transaction(function () use ($items, $itemModels, $peminjaman, &$hasApproved, $validated, $user, $isRequesterReviewStage) {
            foreach ($items as $itemId => $payload) {
                $item = $itemModels[$itemId];
                $decision = $payload['decision'];
                $requestedQty = (int) $item->qty;
                $maxApprovedQty = ! $isRequesterReviewStage && $peminjaman->is_inter_area
                    ? min($requestedQty, (int) ($item->approved_qty ?? 0))
                    : $requestedQty;

                $approvedQty = $decision === 'tolak'
                    ? 0
                    : min((int) $payload['approved_qty'], $maxApprovedQty);

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

            if ($isRequesterReviewStage) {
                $peminjaman->update([
                    'requester_review_note' => $validated['review_note'] ?? null,
                    'requester_reviewed_at' => now(),
                    'requester_reviewed_by' => $user->id,
                    'status' => $hasApproved
                        ? Peminjaman::STATUS_MENUNGGU_REVIEW
                        : Peminjaman::STATUS_DITOLAK,
                ]);
            } else {
                $peminjaman->update([
                    'review_note' => $validated['review_note'] ?? null,
                    'reviewed_at' => now(),
                    'reviewed_by' => $user->id,
                    'status' => $hasApproved
                        ? Peminjaman::STATUS_DISETUJUI
                        : Peminjaman::STATUS_DITOLAK,
                ]);
            }
        });

        return response()->json([
            'id' => $peminjaman->id,
            'status' => $peminjaman->status,
        ]);
    }
}

