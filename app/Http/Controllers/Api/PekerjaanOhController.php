<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\MemuatLaporanAlatPeminjaman;
use App\Http\Controllers\Controller;
use App\Models\LaporanAlat;
use App\Models\PekerjaanOh;
use App\Models\Peminjaman;
use App\Models\Role;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class PekerjaanOhController extends Controller
{
    use MemuatLaporanAlatPeminjaman;

    /**
     * Peran yang hanya boleh melihat pekerjaan pada areanya sendiri. Peran lain
     * (sp tool, mgr tool, admin, super admin) boleh melihat lintas area.
     */
    private const PERAN_TERKUNCI_AREA = [
        Role::KEY_USER,
        Role::KEY_PIC_TOOL,
        Role::KEY_GUEST,
    ];

    /**
     * Peran yang boleh membuat, mengubah, dan menghapus pekerjaan. Admin
     * dibatasi pada areanya sendiri; super admin tidak dibatasi.
     */
    private const PERAN_PENGELOLA = [
        Role::KEY_ADMIN,
        Role::KEY_SUPER_ADMIN,
    ];

    private function roleKey(Request $request): string
    {
        return strtolower((string) ($request->user()?->loadMissing('role')->role?->key ?? ''));
    }

    /**
     * Area yang mengunci tampilan pengguna, atau null bila ia boleh melihat
     * seluruh area.
     */
    private function areaTerkunci(Request $request): ?int
    {
        if (! in_array($this->roleKey($request), self::PERAN_TERKUNCI_AREA, true)) {
            return null;
        }

        return (int) ($request->user()?->area_id ?? 0) ?: 0;
    }

    private function bolehMengelola(Request $request): bool
    {
        return in_array($this->roleKey($request), self::PERAN_PENGELOLA, true);
    }

    /**
     * Admin hanya boleh mengelola pekerjaan di areanya sendiri. Pemeriksaan ini
     * ada di server, jadi tidak bisa dilewati dari antarmuka.
     */
    private function pastikanBolehMengelola(Request $request, ?PekerjaanOh $pekerjaan = null): void
    {
        abort_unless($this->bolehMengelola($request), 403, 'Anda tidak berhak mengelola pekerjaan.');

        if ($this->isSuperAdmin($request) || ! $pekerjaan) {
            return;
        }

        abort_unless(
            (int) $pekerjaan->area_id === (int) ($request->user()?->area_id ?? 0),
            403,
            'Pekerjaan ini berada di area lain.'
        );
    }

    public function index(Request $request)
    {
        $search = trim((string) $request->query('search', ''));
        $jenis = trim((string) $request->query('jenis', ''));
        $areaId = (int) $request->query('area_id', 0);

        $query = PekerjaanOh::query()
            ->with(['creator', 'area'])
            ->orderBy('tanggal_mulai')
            ->orderBy('id');

        // Peran yang terkunci area selalu memakai areanya sendiri, berapa pun
        // nilai yang dikirim dari antarmuka.
        $terkunci = $this->areaTerkunci($request);
        if ($terkunci !== null) {
            $areaId = $terkunci;
        }

        if ($areaId > 0) {
            $query->where('area_id', $areaId);
        } elseif ($terkunci !== null) {
            // Terkunci area tetapi akunnya belum punya area: jangan tampilkan
            // apa pun daripada membocorkan seluruh data.
            $query->whereRaw('1 = 0');
        }

        if ($search !== '') {
            $keyword = '%'.mb_strtolower($search).'%';
            $query->where(function (Builder $builder) use ($keyword) {
                $builder
                    ->whereRaw('LOWER(nama) LIKE ?', [$keyword])
                    ->orWhereRaw('LOWER(siteco) LIKE ?', [$keyword])
                    ->orWhereRaw('LOWER(jenis) LIKE ?', [$keyword]);
            });
        }

        if ($jenis !== '' && strtolower($jenis) !== 'semua') {
            $query->where('jenis', $jenis);
        }

        $perPage = (int) $request->query('per_page', 10);
        $perPage = $perPage > 0 ? min($perPage, 100) : 10;
        $rows = $query->paginate($perPage);

        return [
            'data' => $rows->getCollection()
                ->map(fn (PekerjaanOh $pekerjaan) => $this->format($pekerjaan))
                ->values(),
            'ringkasan' => $this->buildRingkasan(clone $query),
            'meta' => [
                'current_page' => $rows->currentPage(),
                'last_page' => $rows->lastPage(),
                'per_page' => $rows->perPage(),
                'total' => $rows->total(),
            ],
            // Dipakai frontend untuk mengisi pilihan filter jenis.
            'jenis_tersedia' => PekerjaanOh::query()
                ->whereNotNull('jenis')
                ->distinct()
                ->orderBy('jenis')
                ->pluck('jenis')
                ->values(),
            // Hak akses dikirim dari server supaya antarmuka tidak perlu
            // menyalin ulang aturan perannya.
            'akses' => [
                'boleh_mengelola' => $this->bolehMengelola($request),
                'terkunci_area' => $terkunci !== null,
                'area_sendiri' => (int) ($request->user()?->area_id ?? 0) ?: null,
            ],
        ];
    }

    /**
     * Daftar pengguna pada area aktif untuk pilihan site coordinator.
     */
    public function penggunaArea(Request $request)
    {
        $user = $request->user();
        $user?->loadMissing('role');
        $roleKey = strtolower((string) ($user?->role?->key ?? ''));

        // Super admin dan guest mengikuti area yang sedang dipilih di header.
        $areaId = in_array($roleKey, [Role::KEY_SUPER_ADMIN, Role::KEY_GUEST], true) && $request->filled('area_id')
            ? (int) $request->input('area_id')
            : (int) ($user?->area_id ?? 0);

        if (! $areaId) {
            return [];
        }

        return User::query()
            ->where('area_id', $areaId)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (User $pengguna) => [
                'id' => $pengguna->id,
                'nama' => $pengguna->name,
            ])
            ->values();
    }

    /**
     * Jumlah pekerjaan per tahap jadwal. Dihitung di PHP supaya tidak bergantung
     * pada fungsi tanggal khas satu database.
     *
     * @return array<string, int>
     */
    /**
     * Daftar ringkas untuk pilihan "Pekerjaan" pada form peminjaman. Dibuka
     * untuk semua pengguna yang sudah login, karena peminjam perlu memilih
     * pekerjaan walau tidak berhak mengelola data overhaul.
     */
    public function pilihan(): array
    {
        $hariIni = CarbonImmutable::now()->startOfDay();

        $data = PekerjaanOh::query()
            ->orderBy('tanggal_mulai', 'desc')
            ->orderBy('id', 'desc')
            ->get(['id', 'jenis', 'nama', 'tanggal_mulai', 'tanggal_selesai'])
            ->map(fn (PekerjaanOh $pekerjaan) => [
                'id' => $pekerjaan->id,
                'nama' => $pekerjaan->nama,
                'jenis' => $pekerjaan->jenis,
                'status' => $this->statusPekerjaan($pekerjaan, $hariIni),
                'periode' => $this->labelPeriode($pekerjaan),
            ])
            // Yang sedang berlangsung paling mungkin dipilih dan yang sudah
            // selesai paling jarang, jadi urutannya mengikuti itu.
            ->sortBy(fn (array $baris) => match ($baris['status']) {
                'berlangsung' => 0,
                'akan_datang' => 1,
                default => 2,
            })
            ->values();

        return ['data' => $data];
    }

    /**
     * Detail satu pekerjaan overhaul beserta peminjaman yang memakainya.
     * Alat tiap peminjaman ikut dikirim supaya rinciannya bisa dibuka langsung
     * di layar tanpa permintaan tambahan.
     */
    public function detail(Request $request, PekerjaanOh $pekerjaanOh): array
    {
        // Peran yang terkunci area tidak boleh membuka pekerjaan area lain,
        // termasuk lewat URL langsung.
        $terkunci = $this->areaTerkunci($request);
        if ($terkunci !== null) {
            abort_unless(
                $terkunci > 0 && (int) $pekerjaanOh->area_id === $terkunci,
                403,
                'Pekerjaan ini berada di area lain.'
            );
        }

        $hariIni = CarbonImmutable::now()->startOfDay();

        $peminjamans = $pekerjaanOh->peminjamans()
            ->with(['user', 'area', 'requesterArea', 'bidangPekerjaan', 'items.alat'])
            ->orderByDesc('tanggal_pinjam')
            ->orderByDesc('id')
            ->get();

        // Laporan seluruh peminjaman diambil sekali, bukan per peminjaman.
        $this->prefetchLaporanAlat($peminjamans);

        $daftar = $peminjamans->map(fn (Peminjaman $peminjaman) => $this->formatPeminjaman($peminjaman))->values();

        return [
            'pekerjaan' => array_merge(
                $this->format($pekerjaanOh->loadMissing(['creator', 'area'])),
                ['status' => $this->statusPekerjaan($pekerjaanOh, $hariIni)]
            ),
            'ringkasan' => [
                'jumlah_peminjaman' => $daftar->count(),
                'total_alat_diminta' => (int) $daftar->sum('total_diminta'),
                'total_alat_disetujui' => (int) $daftar->sum('total_disetujui'),
                // Nama areanya langsung dikirim, bukan jumlahnya, supaya
                // pembaca tahu area mana saja yang terlibat.
                'total_kerusakan' => (int) $daftar->sum('total_kerusakan'),
                'total_kehilangan' => (int) $daftar->sum('total_kehilangan'),
                'area_terlibat' => $daftar
                    ->flatMap(fn (array $peminjaman) => [$peminjaman['area_peminjam'], $peminjaman['area']])
                    ->filter()
                    ->unique()
                    ->sort()
                    ->values(),
            ],
            'peminjamans' => $daftar,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function formatPeminjaman(Peminjaman $peminjaman): array
    {
        // Laporan dicocokkan dengan aturan yang sama seperti halaman
        // Pengiriman, supaya "laporan milik peminjaman ini" berarti hal yang
        // sama di seluruh aplikasi.
        $laporanPerAlat = $this->laporanAlatUntuk($peminjaman);

        $items = $peminjaman->items
            ->sortBy(fn ($item) => mb_strtolower((string) ($item->alat?->nama ?? '')))
            ->values()
            ->map(function ($item) use ($laporanPerAlat) {
                $laporan = collect($laporanPerAlat->get($item->alat_id, []))
                    ->map(fn (LaporanAlat $baris) => [
                        'id' => $baris->id,
                        'kategori' => $baris->kategori,
                        'status' => $baris->status ?? LaporanAlat::STATUS_DILAPORKAN,
                        'jumlah' => (int) $baris->jumlah,
                        'deskripsi' => $baris->deskripsi,
                        'tanggal' => ($baris->tanggal_laporan ?? $baris->created_at)?->format('d M Y'),
                        'pelapor' => $baris->user?->name,
                    ])
                    ->values();

                return [
                    'id' => $item->id,
                    'kode' => $item->alat?->kode,
                    'nama' => $item->alat?->nama ?? '-',
                    'jenis' => $item->alat?->jenis_alat,
                    'qty' => (int) $item->qty,
                    'qty_disetujui' => (int) $item->approved_qty,
                    'qty_dikembalikan' => (int) $item->returned_qty,
                    'laporan' => $laporan,
                    'jumlah_kerusakan' => $laporan
                        ->where('kategori', LaporanAlat::CATEGORY_KERUSAKAN)
                        ->sum('jumlah'),
                    'jumlah_kehilangan' => $laporan
                        ->where('kategori', LaporanAlat::CATEGORY_KEHILANGAN)
                        ->sum('jumlah'),
                ];
            });

        return [
            'id' => $peminjaman->id,
            'peminjam' => $peminjaman->user?->name ?? '-',
            'area' => $peminjaman->area?->name,
            'area_peminjam' => $peminjaman->requesterArea?->name,
            'kategori' => $peminjaman->kategori,
            'status' => $peminjaman->status,
            'bidang' => $peminjaman->bidangPekerjaan?->nama,
            'tanggal_pinjam' => $peminjaman->tanggal_pinjam?->format('d M Y'),
            'tanggal_kembali' => $peminjaman->tanggal_kembali?->format('d M Y'),
            'jumlah_jenis_alat' => $items->count(),
            'total_diminta' => (int) $items->sum('qty'),
            'total_disetujui' => (int) $items->sum('qty_disetujui'),
            'total_kerusakan' => (int) $items->sum('jumlah_kerusakan'),
            'total_kehilangan' => (int) $items->sum('jumlah_kehilangan'),
            'items' => $items,
        ];
    }

    private function buildRingkasan($query): array
    {
        $hariIni = CarbonImmutable::now()->startOfDay();
        $ringkasan = ['total' => 0, 'akan_datang' => 0, 'berlangsung' => 0, 'selesai' => 0];

        $query->reorder()->get(['tanggal_mulai', 'tanggal_selesai'])->each(function ($baris) use (&$ringkasan, $hariIni) {
            $ringkasan['total']++;
            $ringkasan[$this->statusPekerjaan($baris, $hariIni)]++;
        });

        return $ringkasan;
    }

    /**
     * Satu-satunya tempat status pekerjaan ditentukan, supaya angka ringkasan
     * dan urutan pilihan tidak pernah memakai definisi yang berbeda.
     */
    private function statusPekerjaan(PekerjaanOh $pekerjaan, CarbonImmutable $hariIni): string
    {
        $mulai = $pekerjaan->tanggal_mulai;
        $selesai = $pekerjaan->tanggal_selesai;

        if ($mulai && $mulai->startOfDay()->greaterThan($hariIni)) {
            return 'akan_datang';
        }

        if ($selesai && $selesai->startOfDay()->lessThan($hariIni)) {
            return 'selesai';
        }

        return 'berlangsung';
    }

    private function labelPeriode(PekerjaanOh $pekerjaan): string
    {
        $mulai = $pekerjaan->tanggal_mulai?->format('d M Y');
        $selesai = $pekerjaan->tanggal_selesai?->format('d M Y');

        if ($mulai && $selesai) {
            return $mulai.' - '.$selesai;
        }

        return $mulai ?? $selesai ?? '';
    }

    public function store(Request $request): JsonResponse
    {
        $this->pastikanBolehMengelola($request);

        $validated = $this->validatePayload($request);
        $validated['area_id'] = $this->resolveAreaId($request);

        $pekerjaan = PekerjaanOh::create($validated + [
            'created_by' => $request->user()?->id,
        ]);

        return response()->json([
            'message' => 'Pekerjaan overhaul berhasil ditambahkan.',
            'data' => $this->format($pekerjaan->load(['creator', 'area'])),
        ], 201);
    }

    public function update(Request $request, PekerjaanOh $pekerjaanOh): JsonResponse
    {
        $this->pastikanBolehMengelola($request, $pekerjaanOh);

        $validated = $this->validatePayload($request);

        // Hanya super admin yang boleh memindahkan pekerjaan ke area lain.
        // Peran lain tetap memakai area yang sudah tersimpan.
        if ($this->isSuperAdmin($request)) {
            $validated['area_id'] = $this->resolveAreaId($request);
        }

        $pekerjaanOh->update($validated);

        return response()->json([
            'message' => 'Pekerjaan overhaul berhasil diperbarui.',
            'data' => $this->format($pekerjaanOh->fresh(['creator', 'area'])),
        ]);
    }

    private function isSuperAdmin(Request $request): bool
    {
        return strtolower((string) ($request->user()?->loadMissing('role')->role?->key ?? ''))
            === Role::KEY_SUPER_ADMIN;
    }

    /**
     * Area pekerjaan mengikuti area akun yang membuat. Super admin tidak
     * terikat pada satu area, jadi ia wajib memilih areanya sendiri.
     */
    private function resolveAreaId(Request $request): int
    {
        $user = $request->user();

        if ($this->isSuperAdmin($request)) {
            $validated = $request->validate([
                'area_id' => ['required', 'integer', 'exists:areas,id'],
            ], [
                'area_id.required' => 'Area wajib dipilih.',
                'area_id.exists' => 'Area yang dipilih tidak ditemukan.',
            ]);

            return (int) $validated['area_id'];
        }

        $areaId = (int) ($user?->area_id ?? 0);

        if ($areaId <= 0) {
            throw ValidationException::withMessages([
                'area_id' => ['Akun Anda belum memiliki area, sehingga pekerjaan overhaul tidak dapat disimpan.'],
            ]);
        }

        return $areaId;
    }

    public function destroy(Request $request, PekerjaanOh $pekerjaanOh): JsonResponse
    {
        $this->pastikanBolehMengelola($request, $pekerjaanOh);

        $pekerjaanOh->delete();

        return response()->json(['message' => 'Pekerjaan overhaul berhasil dihapus.']);
    }

    /**
     * @return array<string, mixed>
     */
    private function validatePayload(Request $request): array
    {
        return $request->validate([
            'jenis' => ['nullable', 'string', 'max:100'],
            'nama' => ['required', 'string', 'max:255'],
            'tanggal_mulai' => ['required', 'date'],
            'tanggal_selesai' => ['nullable', 'date', 'after_or_equal:tanggal_mulai'],
            'siteco' => ['nullable', 'string', 'max:255'],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function format(PekerjaanOh $pekerjaan): array
    {
        return [
            'id' => $pekerjaan->id,
            'area_id' => $pekerjaan->area_id,
            'area_nama' => $pekerjaan->area?->name,
            'jenis' => $pekerjaan->jenis,
            'nama' => $pekerjaan->nama,
            'lama_oh' => $pekerjaan->lamaOh(),
            'siteco' => $pekerjaan->siteco,
            'tanggal_mulai' => $pekerjaan->tanggal_mulai?->format('d F Y'),
            'tanggal_mulai_iso' => $pekerjaan->tanggal_mulai?->toDateString(),
            'tanggal_selesai' => $pekerjaan->tanggal_selesai?->format('d F Y'),
            'tanggal_selesai_iso' => $pekerjaan->tanggal_selesai?->toDateString(),
            'created_by_nama' => $pekerjaan->creator?->name ?? '-',
            'created_at' => $pekerjaan->created_at?->format('d/m/Y H:i'),
        ];
    }
}
