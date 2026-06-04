<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\Peminjaman;
use App\Models\PeminjamanItem;
use App\Models\Role;
use App\Models\SuratJalan;
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
        $isSpTool = $roleKey === Role::KEY_SP_TOOL;
        $isPicTools = $roleKey === Role::KEY_PIC_TOOL;
        $isMgrTool = $roleKey === Role::KEY_MGR_TOOL;
        $isUser = $roleKey === Role::KEY_USER;
        $isSuperAdmin = $roleKey === Role::KEY_SUPER_ADMIN;
        $isAdmin = $roleKey === Role::KEY_ADMIN;

        if (! $isSpTool && ! $isPicTools && ! $isMgrTool && ! $isUser && ! $isAdmin && ! $isSuperAdmin) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $search = trim((string) $request->query('search', ''));
        $areaIdParam = $request->query('area_id');
        $kategori = trim((string) ($request->query('kategori', $request->query('category', ''))));
        $areaScope = strtolower(trim((string) $request->query('area_scope', '')));
        $areaColumn = $areaScope === 'requester' ? 'requester_area_id' : 'area_id';

        $query = Peminjaman::query()
            ->with([
                'items' => function ($sub) {
                    $sub->where('approved_qty', '>', 0);
                },
                'items.alat.area',
                'suratJalans',
                'area',
                'requesterArea',
                'reviewer',
                'requesterReviewer',
                'user',
            ])
            ->whereIn('status', Peminjaman::shippingHistoryStatuses())
            ->whereHas('items', function ($sub) {
                $sub->where('approved_qty', '>', 0);
            })
            ->orderByDesc('created_at');

        if ($kategori === Peminjaman::KATEGORI_ANTAR_AREA) {
            $query->where(function ($sub) {
                $sub->where('kategori', Peminjaman::KATEGORI_ANTAR_AREA)
                    ->orWhere('is_inter_area', true);
            });
        } elseif ($kategori === Peminjaman::KATEGORI_INTRA_AREA) {
            $query
                ->where(function ($sub) {
                    $sub->where('kategori', Peminjaman::KATEGORI_INTRA_AREA)
                        ->orWhereNull('kategori');
                })
                ->where(function ($sub) {
                    $sub->where('is_inter_area', false)
                        ->orWhereNull('is_inter_area');
                });
        }

        if ($isSuperAdmin) {
            if (! empty($areaIdParam)) {
                $this->applyAreaScopeFilter($query, (int) $areaIdParam, $areaScope, $areaColumn);
            }
        } elseif ($isAdmin) {
            $areaId = $user->area_id;
            if (! $areaId) {
                return response()->json([]);
            }
            $this->applyAreaScopeFilter($query, (int) $areaId, $areaScope, $areaColumn);
        } elseif ($isSpTool || $isPicTools || $isMgrTool) {
            $areaId = $user->area_id;
            if (! $areaId) {
                return response()->json([]);
            }
            $this->applyAreaScopeFilter($query, (int) $areaId, $areaScope, $areaColumn);
        } else {
            $query->where('user_id', $user->id);
        }

        if ($search !== '') {
            $query->where(function ($sub) use ($search) {
                $sub->where('pekerjaan', 'like', '%' . $search . '%')
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
                    'code' => $alat?->kode ?? '-',
                    'qty' => (int) $item->qty,
                    'approved_qty' => (int) ($item->approved_qty ?? 0),
                    'returned_qty' => (int) ($item->returned_qty ?? 0),
                    'remaining_qty' => max((int) ($item->approved_qty ?? 0) - (int) ($item->returned_qty ?? 0), 0),
                    'review_status' => $item->review_status ?? 'Menunggu Review',
                    'rejection_reason' => $item->rejection_reason,
                ];
            })->values();

            $suratJalans = $peminjaman->suratJalans->values();
            $suratJalanPengiriman = $suratJalans->first();
            $suratJalanPengembalian = $suratJalans->count() > 1 ? $suratJalans->last() : null;
            $suratJalanItems = $suratJalans
                ->map(function (SuratJalan $suratJalan, int $index) {
                    return [
                        'id' => $suratJalan->id,
                        'label' => $index === 0 ? 'Surat Jalan Masuk' : 'Surat Jalan Keluar ' . $index,
                        'pengirim_nama' => $suratJalan->pengirim_nama,
                        'path' => $suratJalan->path,
                        'url' => $suratJalan->path
                            ? url('/storage/' . ltrim($suratJalan->path, '/'))
                            : null,
                        'original_name' => $suratJalan->original_name,
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
                'return_date' => $peminjaman->tanggal_kembali
                    ? $peminjaman->tanggal_kembali->format('d M Y')
                    : null,
                'item_count' => $peminjaman->items->sum('approved_qty'),
                'status' => $peminjaman->status,
                'kategori' => $peminjaman->kategori ?? Peminjaman::KATEGORI_INTRA_AREA,
                'pengirim_nama' => $suratJalanPengiriman?->pengirim_nama,
                'surat_jalan_path' => $suratJalanPengiriman?->path,
                'surat_jalan_url' => $suratJalanPengiriman?->path
                    ? url('/storage/' . ltrim($suratJalanPengiriman->path, '/'))
                    : null,
                'pengembali_nama' => $suratJalanPengembalian?->pengirim_nama,
                'surat_jalan_pengembalian_path' => $suratJalanPengembalian?->path,
                'surat_jalan_pengembalian_url' => $suratJalanPengembalian?->path
                    ? url('/storage/' . ltrim($suratJalanPengembalian->path, '/'))
                    : null,
                'surat_jalan_items' => $suratJalanItems,
                'tools' => $tools,
            ];
        })->values();
    }

    private function applyAreaScopeFilter($query, int $areaId, string $areaScope, string $areaColumn): void
    {
        if (in_array($areaScope, ['source', 'requester'], true)) {
            $query->where($areaColumn, $areaId);
            return;
        }

        $query->where(function ($sub) use ($areaId) {
            $sub->where(function ($intra) use ($areaId) {
                $intra
                    ->where(function ($category) {
                        $category
                            ->where('kategori', Peminjaman::KATEGORI_INTRA_AREA)
                            ->orWhereNull('kategori');
                    })
                    ->where(function ($interFlag) {
                        $interFlag
                            ->where('is_inter_area', false)
                            ->orWhereNull('is_inter_area');
                    })
                    ->where('area_id', $areaId);
            })->orWhere(function ($antar) use ($areaId) {
                $antar
                    ->where(function ($category) {
                        $category
                            ->where('kategori', Peminjaman::KATEGORI_ANTAR_AREA)
                            ->orWhere('is_inter_area', true);
                    })
                    ->where('requester_area_id', $areaId);
            });
        });
    }
}
