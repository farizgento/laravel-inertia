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
        $isAreaSwitcherRole = in_array($roleKey, [Role::KEY_GUEST, Role::KEY_SUPER_ADMIN], true);
        $canChooseDashboardArea = in_array($roleKey, [Role::KEY_SP_TOOL, Role::KEY_MGR_TOOL], true);
        $showOperationalInsights = in_array($roleKey, [
            Role::KEY_GUEST,
            Role::KEY_SP_TOOL,
            Role::KEY_PIC_TOOL,
            Role::KEY_MGR_TOOL,
            Role::KEY_SUPER_ADMIN,
        ], true);

        $effectiveAreaId = ($isAreaSwitcherRole || $canChooseDashboardArea)
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

        $alatTotals = (clone $alatQuery)
            ->selectRaw('COUNT(*) as total_jenis, COALESCE(SUM(total_aset), 0) as total_aset')
            ->first();

        $summary = [
            'total_peminjaman' => (int) $statusCounts->sum(),
            'total_aset_area' => (int) ($alatTotals->total_aset ?? 0),
            'total_jenis_alat_area' => (int) ($alatTotals->total_jenis ?? 0),
            'menunggu_review' =>
                (int) ($statusCounts[Peminjaman::STATUS_PERLU_DIREVIEW] ?? 0) +
                (int) ($statusCounts[Peminjaman::STATUS_PERLU_DISETUJUI] ?? 0),
            'sedang_berjalan' =>
                (int) ($statusCounts[Peminjaman::STATUS_DISETUJUI] ?? 0) +
                (int) ($statusCounts[Peminjaman::STATUS_DIKIRIM] ?? 0) +
                (int) ($statusCounts[Peminjaman::STATUS_DITERIMA] ?? 0) +
                (int) ($statusCounts[Peminjaman::STATUS_DIKEMBALIKAN_PARTIALS] ?? 0),
            'selesai' =>
                (int) ($statusCounts[Peminjaman::STATUS_DIKEMBALIKAN_SEMUANYA] ?? 0) +
                (int) ($statusCounts[Peminjaman::STATUS_SELESAI] ?? 0),
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
                'can_choose_dashboard_area' => $canChooseDashboardArea,
                'show_operational_insights' => $showOperationalInsights,
                'generated_at' => now()->toIso8601String(),
            ],
        ]);
    }

    private function laporanAreaQuery(?int $areaId): Builder
    {
        return LaporanAlat::query()
            ->when($areaId, function (Builder $query) use ($areaId) {
                $query->where('area_id', $areaId);
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
            ->selectRaw('kategori, EXTRACT(MONTH FROM created_at) as month_number, COALESCE(SUM(jumlah), 0) as total_jumlah')
            ->groupBy('kategori')
            ->groupByRaw('EXTRACT(MONTH FROM created_at)')
            ->get();

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
            $monthNumber = (int) ($row->month_number ?? 0);
            if ($monthNumber < 1 || $monthNumber > 12) {
                continue;
            }

            $monthKey = $currentYearStart->month($monthNumber)->format('Y-m');
            if (! $monthly->has($monthKey)) {
                continue;
            }

            $entry = $monthly->get($monthKey);
            $totalJumlah = (int) ($row->total_jumlah ?? 0);
            if ($row->kategori === LaporanAlat::CATEGORY_KERUSAKAN) {
                $entry['kerusakan'] += $totalJumlah;
            }

            if ($row->kategori === LaporanAlat::CATEGORY_KEHILANGAN) {
                $entry['kehilangan'] += $totalJumlah;
            }

            $monthly->put($monthKey, $entry);
        }

        $series = $monthly->values();
        $alatTotals = Alat::query()
            ->selectRaw('COUNT(*) as total_jenis, COALESCE(SUM(total_aset), 0) as total_aset')
            ->first();

        return [
            'kerusakan_tahunan' => (int) $series->sum('kerusakan'),
            'kehilangan_tahunan' => (int) $series->sum('kehilangan'),
            'total_aset_semua_area' => (int) ($alatTotals->total_aset ?? 0),
            'total_jenis_alat_semua_area' => (int) ($alatTotals->total_jenis ?? 0),
            'series' => $series,
        ];
    }
}
