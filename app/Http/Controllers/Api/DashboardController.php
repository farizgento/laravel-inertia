<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Alat;
use App\Models\Area;
use App\Models\LaporanAlat;
use App\Models\Peminjaman;
use App\Models\Role;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $validated = $request->validate([
            'area_id' => ['nullable', 'integer', 'exists:areas,id'],
        ]);

        $user->loadMissing(['role', 'area']);
        $roleKey = strtolower((string) ($user->role?->key ?? ''));
        $isAreaSwitcherRole = in_array($roleKey, [Role::KEY_MGR_TOOL, Role::KEY_SUPER_ADMIN], true);
        $showOperationalInsights = in_array($roleKey, [
            Role::KEY_SP_TOOL,
            Role::KEY_PIC_TOOLS,
            Role::KEY_MGR_TOOL,
            Role::KEY_SUPER_ADMIN,
        ], true);

        $effectiveAreaId = $isAreaSwitcherRole
            ? ($validated['area_id'] ?? $user->area_id)
            : $user->area_id;

        $effectiveArea = $effectiveAreaId
            ? Area::query()->select(['id', 'name'])->find($effectiveAreaId)
            : $user->area;

        $peminjamanQuery = Peminjaman::query();
        $alatQuery = Alat::query();

        if ($effectiveAreaId) {
            $peminjamanQuery->where('area_id', $effectiveAreaId);
            $alatQuery->where('area_id', $effectiveAreaId);
        } else {
            $peminjamanQuery->whereRaw('1 = 0');
            $alatQuery->whereRaw('1 = 0');
        }

        $statusCounts = (clone $peminjamanQuery)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->map(fn ($value) => (int) $value);

        $summary = [
            'total_peminjaman' => (clone $peminjamanQuery)->count(),
            'total_aset_area' => (int) (clone $alatQuery)->sum('total_aset'),
            'total_jenis_alat_area' => (clone $alatQuery)->count(),
            'menunggu_review' => (int) ($statusCounts['Menunggu Review'] ?? 0),
            'sedang_berjalan' =>
                (int) ($statusCounts['Dipesan'] ?? 0) +
                (int) ($statusCounts['Disiapkan'] ?? 0) +
                (int) ($statusCounts['Terkirim'] ?? 0) +
                (int) ($statusCounts['Diterima'] ?? 0),
            'selesai' => (int) ($statusCounts['Dikembalikan'] ?? 0),
            'laporan_aktif' => $effectiveAreaId
                ? $this->laporanAreaQuery($effectiveAreaId)
                    ->whereIn('status', ['Dilaporkan', 'Disetujui'])
                    ->count()
                : 0,
        ];

        $insights = null;
        if ($showOperationalInsights) {
            $insights = $this->buildOperationalInsights($effectiveAreaId);
        }

        return response()->json([
            'area' => [
                'id' => $effectiveArea?->id,
                'name' => $effectiveArea?->name ?? 'Area belum dipilih',
            ],
            'summary' => $summary,
            'insights' => $insights,
            'meta' => [
                'role_key' => $roleKey,
                'is_area_switcher' => $isAreaSwitcherRole,
                'show_operational_insights' => $showOperationalInsights,
                'generated_at' => now()->toIso8601String(),
            ],
        ]);
    }

    private function laporanAreaQuery(?int $areaId): Builder
    {
        return LaporanAlat::query()
            ->when($areaId, function (Builder $query) use ($areaId) {
                $query->whereHas('alat', function (Builder $alatQuery) use ($areaId) {
                    $alatQuery->where('area_id', $areaId);
                });
            }, function (Builder $query) {
                $query->whereRaw('1 = 0');
            });
    }

    private function buildOperationalInsights(?int $areaId): array
    {
        $currentYearStart = CarbonImmutable::now()->startOfYear();
        $nextYearStart = $currentYearStart->addYear();
        $rows = $this->laporanAreaQuery($areaId)
            ->whereIn('kategori', [
                LaporanAlat::CATEGORY_KERUSAKAN,
                LaporanAlat::CATEGORY_KEHILANGAN,
            ])
            ->where('created_at', '>=', $currentYearStart->toDateTimeString())
            ->where('created_at', '<', $nextYearStart->toDateTimeString())
            ->get(['kategori', 'created_at']);

        $monthly = collect(range(0, 11))
            ->map(function (int $offset) use ($currentYearStart) {
                $month = $currentYearStart->addMonths($offset);

                return [
                    'key' => $month->format('Y-m'),
                    'label' => $month->translatedFormat('M'),
                    'kerusakan' => 0,
                    'kehilangan' => 0,
                ];
            })
            ->keyBy('key');

        foreach ($rows as $row) {
            $monthKey = optional($row->created_at)->format('Y-m');
            if (! $monthKey || ! $monthly->has($monthKey)) {
                continue;
            }

            $entry = $monthly->get($monthKey);
            if ($row->kategori === LaporanAlat::CATEGORY_KERUSAKAN) {
                $entry['kerusakan'] += 1;
            }

            if ($row->kategori === LaporanAlat::CATEGORY_KEHILANGAN) {
                $entry['kehilangan'] += 1;
            }

            $monthly->put($monthKey, $entry);
        }

        return [
            'kerusakan_tahunan' => (int) $rows->where('kategori', LaporanAlat::CATEGORY_KERUSAKAN)->count(),
            'kehilangan_tahunan' => (int) $rows->where('kategori', LaporanAlat::CATEGORY_KEHILANGAN)->count(),
            'total_aset_semua_area' => (int) Alat::query()->sum('total_aset'),
            'total_jenis_alat_semua_area' => (int) Alat::query()->count(),
            'series' => $monthly->values(),
        ];
    }
}
