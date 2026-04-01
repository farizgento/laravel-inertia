<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Alat;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AlatController extends Controller
{
    private function formatAlat(Alat $alat, int $borrowedQty = 0): array
    {
        $stokTersedia = max(((int) $alat->total_aset) - $borrowedQty, 0);

        return [
            'id' => $alat->id,
            'kode' => sprintf('ALT-%03d', $alat->id),
            'nama' => $alat->nama,
            'stok' => $stokTersedia,
            'total_aset' => (int) $alat->total_aset,
            'stok_tersedia' => $stokTersedia,
            'deskripsi' => '',
            'lokasi' => $alat->area?->name ?? 'Area tidak diketahui',
            'area_name' => $alat->area?->name ?? 'Area tidak diketahui',
            'area_id' => $alat->area_id,
        ];
    }

    private function borrowedMap(array $alatIds): array
    {
        if (! $alatIds) {
            return [];
        }

        return DB::table('peminjaman_items as items')
            ->join('peminjamans as pem', 'pem.id', '=', 'items.peminjaman_id')
            ->whereIn('items.alat_id', $alatIds)
            ->whereIn('pem.status', ['Menunggu Review', 'Dipesan', 'Disiapkan', 'Terkirim', 'Diterima'])
            ->groupBy('items.alat_id')
            ->select(
                'items.alat_id',
                DB::raw(
                    "SUM(CASE
                        WHEN pem.status = 'Menunggu Review' THEN items.qty
                        WHEN pem.status IN ('Dipesan', 'Disiapkan', 'Terkirim', 'Diterima') THEN COALESCE(items.approved_qty, 0)
                        ELSE 0
                    END) as total"
                )
            )
            ->pluck('total', 'alat_id')
            ->map(fn ($value) => (int) $value)
            ->all();
    }

    public function index(Request $request)
    {
        $search = trim((string) $request->query('search', ''));
        $areaId = $request->query('area_id');
        $perPage = (int) $request->query('per_page', 0);
        $shouldPaginate = $request->has('per_page') || $request->has('page');
        $perPageNormalized = $shouldPaginate ? ($perPage > 0 ? min($perPage, 100) : 8) : 0;

        $user = $request->user();
        $roleKey = strtolower((string) ($user?->role?->key ?? ''));
        $isAdmin = in_array($roleKey, [Role::KEY_ADMIN, Role::KEY_SUPER_ADMIN], true);
        $isMgrTool = $roleKey === Role::KEY_MGR_TOOL;

        if ($user && ! $isAdmin) {
            if ($isMgrTool) {
                $areaId = $request->query('area_id') ?: $user->area_id;
            } else {
                $areaId = $user->area_id;
            }
            if (! $areaId) {
                if ($shouldPaginate) {
                    return [
                        'data' => [],
                        'meta' => [
                            'current_page' => 1,
                            'last_page' => 1,
                            'per_page' => $perPageNormalized ?: 8,
                            'total' => 0,
                        ],
                    ];
                }

                return collect();
            }
        }

        $query = Alat::query()->with('area');

        if ($search !== '') {
            $query->where('nama', 'like', '%' . $search . '%');
        }

        if (! empty($areaId)) {
            $query->where('area_id', $areaId);
        }

        if ($shouldPaginate) {
            $alats = $query->orderBy('nama')->paginate($perPageNormalized ?: 8);
            $borrowedMap = $this->borrowedMap($alats->getCollection()->pluck('id')->all());

            $data = $alats->getCollection()->map(function (Alat $alat) use ($borrowedMap) {
                $borrowedQty = $borrowedMap[$alat->id] ?? 0;
                return $this->formatAlat($alat, $borrowedQty);
            })->values();

            return [
                'data' => $data,
                'meta' => [
                    'current_page' => $alats->currentPage(),
                    'last_page' => $alats->lastPage(),
                    'per_page' => $alats->perPage(),
                    'total' => $alats->total(),
                ],
            ];
        }

        $alats = $query->orderBy('nama')->get();
        $borrowedMap = $this->borrowedMap($alats->pluck('id')->all());

        return $alats->map(function (Alat $alat) use ($borrowedMap) {
            $borrowedQty = $borrowedMap[$alat->id] ?? 0;
            return $this->formatAlat($alat, $borrowedQty);
        })->values();
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nama' => ['required', 'string', 'max:255'],
            'total_aset' => ['required', 'integer', 'min:0'],
            'area_id' => ['required', 'integer', 'exists:areas,id'],
        ]);

        $alat = Alat::create($data);
        $alat->load('area');

        $borrowedMap = $this->borrowedMap([$alat->id]);
        $borrowedQty = $borrowedMap[$alat->id] ?? 0;

        return response()->json($this->formatAlat($alat, $borrowedQty), 201);
    }

    public function update(Request $request, Alat $alat)
    {
        $data = $request->validate([
            'nama' => ['required', 'string', 'max:255'],
            'total_aset' => ['required', 'integer', 'min:0'],
            'area_id' => ['required', 'integer', 'exists:areas,id'],
        ]);

        $alat->update($data);
        $alat->load('area');

        $borrowedMap = $this->borrowedMap([$alat->id]);
        $borrowedQty = $borrowedMap[$alat->id] ?? 0;

        return response()->json($this->formatAlat($alat, $borrowedQty));
    }

    public function destroy(Alat $alat)
    {
        $alat->delete();

        return response()->json(['message' => 'Alat berhasil dihapus.']);
    }
}
