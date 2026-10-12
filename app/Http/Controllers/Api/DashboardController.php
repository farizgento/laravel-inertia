<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Alat;
use App\Models\Area;
use App\Models\LaporanAlat;
use App\Models\Peminjaman;
use App\Models\PeminjamanItem;
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
            'mulai' => ['nullable', 'date'],
            'selesai' => ['nullable', 'date', 'after_or_equal:mulai'],
        ]);

        // Default rentang utilisasi: satu tahun penuh pada tahun berjalan.
        $rentangMulai = isset($validated['mulai'])
            ? CarbonImmutable::parse($validated['mulai'])->startOfDay()
            : CarbonImmutable::now()->startOfYear();
        $rentangSelesai = isset($validated['selesai'])
            ? CarbonImmutable::parse($validated['selesai'])->endOfDay()
            : CarbonImmutable::now()->endOfYear();

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

        $utilisasi = $this->buildUtilisasiAlat($effectiveAreaId, $rentangMulai, $rentangSelesai);

        return response()->json([
            'area' => [
                'id' => $effectiveArea?->id,
                'name' => $effectiveArea?->name ?? 'Area belum dipilih',
            ],
            'summary' => $summary,
            'insights' => $insights,
            'utilisasi' => $utilisasi,
            'meta' => [
                'role_key' => $roleKey,
                'is_area_switcher' => $isAreaSwitcherRole,
                'can_choose_dashboard_area' => $canChooseDashboardArea,
                'show_operational_insights' => $showOperationalInsights,
                'generated_at' => now()->toIso8601String(),
            ],
        ]);
    }

    /**
     * Utilisasi alat: seberapa sering tiap alat dipinjam pada rentang tanggal terpilih,
     * termasuk alat yang sama sekali belum pernah dipinjam.
     */
    private function buildUtilisasiAlat(?int $areaId, CarbonImmutable $mulai, CarbonImmutable $selesai): array
    {
        $kosong = [
            'periode' => [
                'mulai' => $mulai->toDateString(),
                'selesai' => $selesai->toDateString(),
                'label' => $mulai->translatedFormat('d M Y') . ' - ' . $selesai->translatedFormat('d M Y'),
            ],
            'ringkasan' => [
                'jenis_alat' => 0,
                'jenis_terpakai' => 0,
                'jenis_tidak_terpakai' => 0,
                'persen_utilisasi' => 0,
                'total_unit_dipinjam' => 0,
                'aset_menganggur' => 0,
            ],
            'paling_sering' => [],
            'jarang_dipakai' => [],
            'tren_bulanan' => [],
        ];

        if (! $areaId) {
            return $kosong;
        }

        $alats = Alat::query()
            ->where('area_id', $areaId)
            ->orderBy('nama')
            ->get(['id', 'kode', 'nama', 'jenis_alat', 'total_aset']);

        if ($alats->isEmpty()) {
            return $kosong;
        }

        $pemakaian = PeminjamanItem::query()
            ->join('peminjamans as p', 'p.id', '=', 'peminjaman_items.peminjaman_id')
            ->join('alats as a', 'a.id', '=', 'peminjaman_items.alat_id')
            ->where('a.area_id', $areaId)
            ->where('p.status', '!=', Peminjaman::STATUS_DITOLAK)
            ->where('p.created_at', '>=', $mulai->toDateTimeString())
            ->where('p.created_at', '<=', $selesai->toDateTimeString())
            ->groupBy('a.id')
            ->selectRaw('a.id as alat_id, COUNT(DISTINCT p.id) as frekuensi, COALESCE(SUM(peminjaman_items.qty), 0) as total_qty, MAX(p.created_at) as terakhir_dipinjam')
            ->get()
            ->keyBy('alat_id');

        $baris = $alats->map(function (Alat $alat) use ($pemakaian) {
            $stat = $pemakaian->get($alat->id);
            $terakhir = $stat?->terakhir_dipinjam;

            return [
                'id' => (int) $alat->id,
                'kode' => $alat->kode ?: '-',
                'nama' => $alat->nama,
                'jenis_alat' => $alat->jenis_alat,
                'total_aset' => (int) $alat->total_aset,
                'frekuensi' => (int) ($stat->frekuensi ?? 0),
                'total_qty' => (int) ($stat->total_qty ?? 0),
                'terakhir_dipinjam' => $terakhir ? CarbonImmutable::parse($terakhir)->translatedFormat('d M Y') : null,
            ];
        });

        $terpakai = $baris->where('frekuensi', '>', 0);

        $palingSering = $terpakai
            ->sortByDesc(fn (array $row) => [$row['frekuensi'], $row['total_qty']])
            ->take(8)
            ->values()
            ->all();

        // Alat yang belum pernah dipinjam ditaruh paling atas, dan di antara yang
        // sama-sama nganggur didahulukan yang asetnya paling banyak.
        $jarangDipakai = $baris
            ->sortBy(fn (array $row) => [$row['frekuensi'], $row['total_qty'], -$row['total_aset']])
            ->take(8)
            ->values()
            ->all();

        $jumlahJenis = $baris->count();
        $jumlahTerpakai = $terpakai->count();

        return [
            'periode' => $kosong['periode'],
            'ringkasan' => [
                'jenis_alat' => $jumlahJenis,
                'jenis_terpakai' => $jumlahTerpakai,
                'jenis_tidak_terpakai' => $jumlahJenis - $jumlahTerpakai,
                // Satu desimal supaya area dengan ratusan jenis alat tidak selalu tampil 0%.
                'persen_utilisasi' => $jumlahJenis > 0 ? round(($jumlahTerpakai / $jumlahJenis) * 100, 1) : 0,
                'total_unit_dipinjam' => (int) $baris->sum('total_qty'),
                'aset_menganggur' => (int) $baris->where('frekuensi', 0)->sum('total_aset'),
            ],
            'paling_sering' => $palingSering,
            'jarang_dipakai' => $jarangDipakai,
            'tren_bulanan' => $this->buildTrenPeminjamanBulanan($areaId, $mulai, $selesai),
        ];
    }

    /**
     * Jumlah transaksi peminjaman per bulan sepanjang rentang terpilih (maksimal 24 bulan).
     */
    private function buildTrenPeminjamanBulanan(int $areaId, CarbonImmutable $mulai, CarbonImmutable $selesai): array
    {
        $rows = Peminjaman::query()
            ->where('area_id', $areaId)
            ->where('status', '!=', Peminjaman::STATUS_DITOLAK)
            ->where('created_at', '>=', $mulai->toDateTimeString())
            ->where('created_at', '<=', $selesai->toDateTimeString())
            ->selectRaw('EXTRACT(YEAR FROM created_at) as tahun, EXTRACT(MONTH FROM created_at) as bulan, COUNT(*) as total')
            ->groupByRaw('EXTRACT(YEAR FROM created_at)')
            ->groupByRaw('EXTRACT(MONTH FROM created_at)')
            ->get()
            ->mapWithKeys(fn ($row) => [
                sprintf('%04d-%02d', (int) $row->tahun, (int) $row->bulan) => (int) $row->total,
            ]);

        $awalBulan = $mulai->startOfMonth();
        $jumlahBulan = min(24, max(1, $awalBulan->diffInMonths($selesai->startOfMonth()) + 1));

        return collect(range(0, $jumlahBulan - 1))
            ->map(function (int $offset) use ($awalBulan, $rows) {
                $bulan = $awalBulan->addMonths($offset);
                $key = $bulan->format('Y-m');

                return [
                    'key' => $key,
                    'label' => $bulan->translatedFormat('M'),
                    'label_panjang' => $bulan->translatedFormat('M Y'),
                    'total' => (int) ($rows[$key] ?? 0),
                ];
            })
            ->all();
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
