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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

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
                    // Disetujui/Ditolak sudah final, jadi laporan aktif hanya yang belum diputuskan.
                    ->where('status', LaporanAlat::STATUS_DILAPORKAN)
                    ->count()
                : 0,
        ];

        // Rincian untuk pie chart pada tiap kartu ringkasan.
        $rincian = [
            'peminjaman' => [
                [
                    'key' => 'review',
                    'label' => 'Perlu Review',
                    'nilai' => $summary['menunggu_review'],
                    'warna' => '#f59e0b',
                ],
                [
                    'key' => 'berjalan',
                    'label' => 'Sedang Berjalan',
                    'nilai' => $summary['sedang_berjalan'],
                    'warna' => '#3b82f6',
                ],
                [
                    'key' => 'selesai',
                    'label' => 'Selesai',
                    'nilai' => $summary['selesai'],
                    'warna' => '#10b981',
                ],
                [
                    'key' => 'ditolak',
                    'label' => 'Ditolak',
                    'nilai' => (int) ($statusCounts[Peminjaman::STATUS_DITOLAK] ?? 0),
                    'warna' => '#f43f5e',
                ],
            ],
            'jenis_alat' => $this->buildRincianJenisAlat($effectiveAreaId),
            'laporan' => $this->buildRincianLaporan($effectiveAreaId),
        ];

        $insights = null;
        if ($showOperationalInsights) {
            $insights = $this->buildOperationalInsights($effectiveAreaId);
        }

        $utilisasi = $this->buildUtilisasiAlat($effectiveAreaId, $rentangMulai, $rentangSelesai);
        $kebutuhan = $this->buildKebutuhanAlat($effectiveAreaId);

        return response()->json([
            'area' => [
                'id' => $effectiveArea?->id,
                'name' => $effectiveArea?->name ?? 'Area belum dipilih',
            ],
            'summary' => $summary,
            'rincian' => $rincian,
            'insights' => $insights,
            'utilisasi' => $utilisasi,
            'kebutuhan' => $kebutuhan,
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
     * Komposisi katalog alat berdasarkan jenisnya. Jenis dengan jumlah kecil
     * digabung menjadi "Lainnya" supaya pie chart tetap terbaca.
     *
     * @return array<int, array<string, mixed>>
     */
    private function buildRincianJenisAlat(?int $areaId): array
    {
        $kosong = [
            'total_jenis' => 0,
            'total_alat' => 0,
            'teratas' => [],
            'lainnya_jenis' => 0,
            'lainnya_alat' => 0,
        ];

        if (! $areaId) {
            return $kosong;
        }

        $jenis = Alat::query()
            ->where('area_id', $areaId)
            ->selectRaw('jenis_alat, COUNT(*) as total')
            ->groupBy('jenis_alat')
            ->orderByDesc('total')
            ->get();

        if ($jenis->isEmpty()) {
            return $kosong;
        }

        // Jenis alat bisa ratusan, jadi hanya sebagian teratas yang ditampilkan
        // sebagai batang; sisanya cukup diringkas menjadi satu baris.
        $batas = 8;
        $teratas = $jenis->take($batas);
        $sisa = $jenis->slice($batas);

        return [
            'total_jenis' => $jenis->count(),
            'total_alat' => (int) $jenis->sum('total'),
            'teratas' => $teratas
                ->values()
                ->map(fn ($baris, $index) => [
                    'key' => 'jenis-'.$index,
                    'label' => $baris->jenis_alat ?: 'Tanpa jenis',
                    'nilai' => (int) $baris->total,
                ])
                ->all(),
            'lainnya_jenis' => $sisa->count(),
            'lainnya_alat' => (int) $sisa->sum('total'),
        ];
    }

    /**
     * Komposisi laporan alat berdasarkan statusnya.
     *
     * @return array<int, array<string, mixed>>
     */
    private function buildRincianLaporan(?int $areaId): array
    {
        if (! $areaId) {
            return [];
        }

        $perStatus = $this->laporanAreaQuery($areaId)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $warna = [
            LaporanAlat::STATUS_DILAPORKAN => '#f59e0b',
            LaporanAlat::STATUS_DISETUJUI => '#0ea5e9',
            LaporanAlat::STATUS_DITOLAK => '#f43f5e',
            LaporanAlat::STATUS_SELESAI_LAMA => '#10b981',
        ];

        $rincian = [];
        foreach ($warna as $status => $kode) {
            $rincian[] = [
                'key' => Str::slug($status),
                'label' => $status,
                'nilai' => (int) ($perStatus[$status] ?? 0),
                'warna' => $kode,
            ];
        }

        // Status di luar daftar baku (bila ada data lama) tetap ditampilkan.
        foreach ($perStatus as $status => $total) {
            if (! array_key_exists($status, $warna)) {
                $rincian[] = [
                    'key' => Str::slug((string) $status),
                    'label' => (string) $status,
                    'nilai' => (int) $total,
                    'warna' => '#94a3b8',
                ];
            }
        }

        return $rincian;
    }

    /**
     * Perbandingan jumlah aset dengan kebutuhan (max demand) per area. Alat yang
     * max demand-nya masih 0 dianggap belum ditentukan, bukan kelebihan, supaya
     * grafiknya tidak menyesatkan selama kebutuhan belum diisi.
     *
     * @return array<string, mixed>
     */
    private function buildKebutuhanAlat(?int $areaId): array
    {
        $kosong = [
            'ringkasan' => [
                'jenis_alat' => 0,
                'kekurangan' => 0,
                'kelebihan' => 0,
                'sesuai' => 0,
                'belum_ditentukan' => 0,
                'unit_kekurangan' => 0,
                'unit_kelebihan' => 0,
            ],
            'paling_kurang' => [],
            'paling_lebih' => [],
        ];

        if (! $areaId) {
            return $kosong;
        }

        $agregat = Alat::query()
            ->where('area_id', $areaId)
            ->selectRaw('COUNT(*) as jenis_alat')
            ->selectRaw('SUM(CASE WHEN max_demand > 0 AND total_aset < max_demand THEN 1 ELSE 0 END) as kekurangan')
            ->selectRaw('SUM(CASE WHEN max_demand > 0 AND total_aset > max_demand THEN 1 ELSE 0 END) as kelebihan')
            ->selectRaw('SUM(CASE WHEN max_demand > 0 AND total_aset = max_demand THEN 1 ELSE 0 END) as sesuai')
            ->selectRaw('SUM(CASE WHEN max_demand = 0 THEN 1 ELSE 0 END) as belum_ditentukan')
            ->selectRaw('SUM(CASE WHEN max_demand > 0 AND total_aset < max_demand THEN max_demand - total_aset ELSE 0 END) as unit_kekurangan')
            ->selectRaw('SUM(CASE WHEN max_demand > 0 AND total_aset > max_demand THEN total_aset - max_demand ELSE 0 END) as unit_kelebihan')
            ->first();

        $ambilTeratas = function (string $arah) use ($areaId) {
            $selisih = $arah === 'kurang' ? 'max_demand - total_aset' : 'total_aset - max_demand';

            return Alat::query()
                ->where('area_id', $areaId)
                ->where('max_demand', '>', 0)
                ->whereColumn('total_aset', $arah === 'kurang' ? '<' : '>', 'max_demand')
                ->orderByRaw($selisih.' DESC')
                ->orderBy('nama')
                ->take(8)
                ->get(['id', 'kode', 'nama', 'jenis_alat', 'total_aset', 'max_demand'])
                ->map(fn (Alat $alat) => [
                    'id' => (int) $alat->id,
                    'kode' => $alat->kode ?: '-',
                    'nama' => $alat->nama,
                    'jenis_alat' => $alat->jenis_alat,
                    'total_aset' => (int) $alat->total_aset,
                    'max_demand' => (int) $alat->max_demand,
                    'selisih' => abs((int) $alat->total_aset - (int) $alat->max_demand),
                ])
                ->values()
                ->all();
        };

        return [
            'ringkasan' => [
                'jenis_alat' => (int) ($agregat->jenis_alat ?? 0),
                'kekurangan' => (int) ($agregat->kekurangan ?? 0),
                'kelebihan' => (int) ($agregat->kelebihan ?? 0),
                'sesuai' => (int) ($agregat->sesuai ?? 0),
                'belum_ditentukan' => (int) ($agregat->belum_ditentukan ?? 0),
                'unit_kekurangan' => (int) ($agregat->unit_kekurangan ?? 0),
                'unit_kelebihan' => (int) ($agregat->unit_kelebihan ?? 0),
            ],
            'paling_kurang' => $ambilTeratas('kurang'),
            'paling_lebih' => $ambilTeratas('lebih'),
        ];
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
            ->selectRaw($this->sqlBagianTanggal('YEAR').' as tahun, '.$this->sqlBagianTanggal('MONTH').' as bulan, COUNT(*) as total')
            ->groupByRaw($this->sqlBagianTanggal('YEAR'))
            ->groupByRaw($this->sqlBagianTanggal('MONTH'))
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

    /**
     * Pengambilan tahun/bulan dari created_at. Oracle memakai EXTRACT, sedangkan
     * SQLite (dipakai pengujian) hanya mengenal strftime.
     */
    private function sqlBagianTanggal(string $bagian): string
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            $format = $bagian === 'YEAR' ? '%Y' : '%m';

            return "CAST(strftime('{$format}', created_at) AS INTEGER)";
        }

        return "EXTRACT({$bagian} FROM created_at)";
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
            ->selectRaw('kategori, '.$this->sqlBagianTanggal('MONTH').' as month_number, COALESCE(SUM(jumlah), 0) as total_jumlah')
            ->groupBy('kategori')
            ->groupByRaw($this->sqlBagianTanggal('MONTH'))
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
