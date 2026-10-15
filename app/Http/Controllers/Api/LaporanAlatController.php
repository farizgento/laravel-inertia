<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Alat;
use App\Models\Area;
use App\Models\AreaAlatStock;
use App\Models\LaporanAlat;
use App\Models\LaporanAlatPhoto;
use App\Models\Role;
use App\Services\DaftarAlatExcelService;
use App\Services\LaporanAlatExcelService;
use App\Support\LaporanPeriod;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class LaporanAlatController extends Controller
{
    private function resolveActiveAreaId(Request $request, string $roleKey, $fallbackAreaId): ?int
    {
        if (in_array($roleKey, [Role::KEY_GUEST, Role::KEY_SUPER_ADMIN], true) && $request->filled('area_id')) {
            return (int) $request->input('area_id');
        }

        return $fallbackAreaId ? (int) $fallbackAreaId : null;
    }

    private function applyReportAreaFilter(Builder $query, int $areaId): Builder
    {
        return $query->where('area_id', $areaId);
    }

    private function applyAccessibleToolFilter(Builder $query, int $areaId): Builder
    {
        return $query->where(function (Builder $builder) use ($areaId) {
            $builder
                ->where('area_id', $areaId)
                ->orWhereHas('areaStocks', function (Builder $stockQuery) use ($areaId) {
                    $stockQuery
                        ->where('area_id', $areaId)
                        ->where('active', true)
                        ->where('qty', '>', 0);
                });
        });
    }

    public function pendingCounts(Request $request)
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
        $isAdmin = in_array($roleKey, [Role::KEY_ADMIN, Role::KEY_SUPER_ADMIN], true);

        if (! ($isSpTool || $isPicTools || $isMgrTool || $isAdmin)) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $areaId = $this->resolveActiveAreaId($request, $roleKey, $user->area_id);

        $baseQuery = LaporanAlat::query()
            ->where('status', 'Dilaporkan');

        if ($isSpTool || $isPicTools || $isMgrTool) {
            if (! $areaId) {
                return response()->json([
                    'kerusakan' => 0,
                    'kehilangan' => 0,
                ]);
            }

            $this->applyReportAreaFilter($baseQuery, $areaId);
        } elseif (! empty($areaId)) {
            $this->applyReportAreaFilter($baseQuery, $areaId);
        }

        $counts = (clone $baseQuery)
            ->selectRaw('kategori, COUNT(*) as total')
            ->groupBy('kategori')
            ->pluck('total', 'kategori');

        return response()->json([
            'kerusakan' => (int) ($counts[LaporanAlat::CATEGORY_KERUSAKAN] ?? 0),
            'kehilangan' => (int) ($counts[LaporanAlat::CATEGORY_KEHILANGAN] ?? 0),
        ]);
    }

    public function indexKehilangan(Request $request)
    {
        return $this->indexByKategori($request, LaporanAlat::CATEGORY_KEHILANGAN);
    }

    public function exportKehilangan(Request $request): BinaryFileResponse
    {
        return $this->exportByKategori($request, LaporanAlat::CATEGORY_KEHILANGAN);
    }

    public function downloadTemplateLaporanAlat(): BinaryFileResponse
    {
        return $this->downloadTemplateFile([
            [
                'path' => public_path('storage/templates/template-surat-laporan-alat.docx'),
                'filename' => 'template-surat-laporan-alat.docx',
            ],
            [
                'path' => public_path('storage/templates/template-surat-kerusakan.docx'),
                'filename' => 'template-surat-kerusakan.docx',
            ],
            [
                'path' => public_path('storage/templates/template-surat-kehilangan.docx'),
                'filename' => 'template-surat-kehilangan.docx',
            ],
        ]);
    }

    public function downloadTemplateKehilangan(): BinaryFileResponse
    {
        return $this->downloadTemplateByKategori(LaporanAlat::CATEGORY_KEHILANGAN);
    }

    public function storeKehilangan(Request $request)
    {
        return $this->storeByKategori($request, LaporanAlat::CATEGORY_KEHILANGAN);
    }

    public function indexKerusakan(Request $request)
    {
        return $this->indexByKategori($request, LaporanAlat::CATEGORY_KERUSAKAN);
    }

    public function exportKerusakan(Request $request): BinaryFileResponse
    {
        return $this->exportByKategori($request, LaporanAlat::CATEGORY_KERUSAKAN);
    }

    public function downloadTemplateKerusakan(): BinaryFileResponse
    {
        return $this->downloadTemplateByKategori(LaporanAlat::CATEGORY_KERUSAKAN);
    }

    public function storeKerusakan(Request $request)
    {
        return $this->storeByKategori($request, LaporanAlat::CATEGORY_KERUSAKAN);
    }

    public function updateKehilanganStatus(Request $request, LaporanAlat $laporan)
    {
        return $this->updateStatusByKategori($request, $laporan, LaporanAlat::CATEGORY_KEHILANGAN);
    }

    public function updateKerusakanStatus(Request $request, LaporanAlat $laporan)
    {
        return $this->updateStatusByKategori($request, $laporan, LaporanAlat::CATEGORY_KERUSAKAN);
    }

    private function indexByKategori(Request $request, string $kategori)
    {
        $context = $this->resolveKategoriContext($request);
        $period = LaporanPeriod::fromRequest($request);
        $perPage = (int) $request->query('per_page', 0);
        $shouldPaginate = $request->has('per_page') || $request->has('page');
        $perPageNormalized = $shouldPaginate ? ($perPage > 0 ? min($perPage, 100) : 10) : 0;

        if ($this->shouldReturnEmptyResult($context)) {
            if ($shouldPaginate) {
                return [
                    'data' => [],
                    'meta' => [
                        'current_page' => 1,
                        'last_page' => 1,
                        'per_page' => $perPageNormalized ?: 10,
                        'total' => 0,
                    ],
                ];
            }

            return collect();
        }

        $query = $this->buildKategoriQuery($request, $kategori, $context, $period);

        if ($shouldPaginate) {
            $laporans = $query->paginate($perPageNormalized ?: 10);
            $data = $laporans->getCollection()
                ->map(fn (LaporanAlat $laporan) => $this->mapLaporan($laporan))
                ->values();

            return [
                'data' => $data,
                'meta' => [
                    'current_page' => $laporans->currentPage(),
                    'last_page' => $laporans->lastPage(),
                    'per_page' => $laporans->perPage(),
                    'total' => $laporans->total(),
                ],
            ];
        }

        return $query->get()
            ->map(fn (LaporanAlat $laporan) => $this->mapLaporan($laporan))
            ->values();
    }

    private function exportByKategori(Request $request, string $kategori): BinaryFileResponse
    {
        $context = $this->resolveKategoriContext($request);
        $period = LaporanPeriod::fromRequest($request);
        $options = $request->validate([
            'nomor_laporan' => ['nullable', 'string', 'max:150'],
            'hasil_pemeriksaan' => ['nullable', 'string', 'max:2000'],
            'usulan' => ['nullable', 'string', 'max:2000'],
            'tempat' => ['nullable', 'string', 'max:100'],
            'tanggal_cetak' => ['nullable', 'date_format:Y-m-d'],
            'penandatangan' => ['nullable', 'string', 'max:150'],
            'jabatan' => ['nullable', 'string', 'max:150'],
        ]);
        $reports = $this->shouldReturnEmptyResult($context)
            ? collect()
            : $this->buildKategoriQuery($request, $kategori, $context, $period)->get();
        $area = $context['area_id'] ? Area::find($context['area_id'])?->name : 'Semua area';
        $description = ['Periode: '.$period->label, 'Area: '.($area ?? '-')];
        if ($request->filled('status')) {
            $description[] = 'Status: '.$request->input('status');
        }
        if ($request->filled('search')) {
            $description[] = 'Pencarian: '.mb_substr((string) $request->input('search'), 0, 150);
        }
        $options['context'] = implode(' | ', $description);

        return app(LaporanAlatExcelService::class)->download($reports, $kategori, $options);
    }

    private function downloadTemplateByKategori(string $kategori): BinaryFileResponse
    {
        return app(LaporanAlatExcelService::class)->download(collect(), $kategori, template: true);
    }

    private function downloadTemplateFile(array $candidates): BinaryFileResponse
    {
        foreach ($candidates as $candidate) {
            $path = (string) ($candidate['path'] ?? '');
            if ($path !== '' && file_exists($path)) {
                return response()->download($path, (string) $candidate['filename'], [
                    'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                ]);
            }
        }

        abort(404, 'Template surat belum tersedia. Pastikan file berada di storage/app/public/templates dan jalankan php artisan storage:link.');
    }

    private function storeByKategori(Request $request, string $kategori)
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
        $isSuperAdmin = $roleKey === Role::KEY_SUPER_ADMIN;
        $isAdmin = in_array($roleKey, [Role::KEY_ADMIN, Role::KEY_SUPER_ADMIN], true);
        $targetAreaId = $this->resolveActiveAreaId($request, $roleKey, $user->area_id);

        if (! ($isSpTool || $isPicTools || $isMgrTool || $isAdmin)) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        if (! $targetAreaId && ! $isSuperAdmin) {
            return response()->json(['message' => 'Area tidak ditemukan.'], 403);
        }

        // Satu pengiriman bisa memuat banyak alat sekaligus. Bentuk lama dengan
        // alat_id + jumlah tunggal tetap diterima supaya pemanggil lama tidak rusak.
        $request->merge([
            'items' => $this->normalizeStoreItems($request),
        ]);

        // Deskripsi diisi per alat. Deskripsi umum boleh diisi sebagai cadangan
        // untuk alat yang deskripsinya dikosongkan, supaya pelaporan massal tidak
        // memaksa mengetik hal yang sama berkali-kali.
        $validated = $request->validate([
            'deskripsi' => ['nullable', 'string', 'max:1000'],
            'tanggal_laporan' => ['nullable', 'date', 'before_or_equal:today'],
            // Bukti wajib minimal 3 foto per alat supaya kondisinya terekam dari
            // beberapa sisi. Foto umum boleh diisi sebagai cadangan untuk alat
            // yang fotonya tidak diunggah sendiri.
            'foto' => ['nullable', 'array', 'min:3', 'max:10'],
            'foto.*' => ['image', 'max:5120'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.alat_id' => ['required', 'integer', 'distinct', 'exists:alats,id'],
            'items.*.jumlah' => ['required', 'integer', 'min:1'],
            'items.*.deskripsi' => ['nullable', 'string', 'max:1000', 'required_without:deskripsi'],
            'items.*.foto' => ['nullable', 'array', 'min:3', 'max:10', 'required_without:foto'],
            'items.*.foto.*' => ['image', 'max:5120'],
        ], [
            'items.required' => 'Pilih minimal satu alat.',
            'items.*.alat_id.distinct' => 'Terdapat alat yang dipilih lebih dari sekali.',
            'items.*.deskripsi.required_without' => 'Isi deskripsi untuk setiap alat, atau isi deskripsi umum.',
            'foto.array' => 'Unggah minimal 3 foto.',
            'foto.min' => 'Unggah minimal 3 foto.',
            'foto.max' => 'Maksimal 10 foto umum.',
            'items.*.foto.required_without' => 'Unggah minimal 3 foto untuk setiap alat, atau isi foto umum.',
            'items.*.foto.min' => 'Unggah minimal 3 foto untuk alat ini.',
            'items.*.foto.max' => 'Maksimal 10 foto per alat.',
        ]);

        $alats = Alat::query()
            ->whereIn('id', collect($validated['items'])->pluck('alat_id')->all())
            ->when($targetAreaId, function (Builder $query) use ($targetAreaId) {
                $this->applyAccessibleToolFilter($query, $targetAreaId);
            }, function (Builder $query) use ($isSuperAdmin, $user) {
                if (! $isSuperAdmin) {
                    $this->applyAccessibleToolFilter($query, (int) $user->area_id);
                }
            })
            ->get()
            ->keyBy('id');

        $tidakDitemukan = collect($validated['items'])
            ->pluck('alat_id')
            ->reject(fn ($alatId) => $alats->has($alatId));

        if ($tidakDitemukan->isNotEmpty()) {
            throw ValidationException::withMessages([
                'items' => [$isAdmin
                    ? 'Ada alat yang tidak ditemukan.'
                    : 'Ada alat yang tidak ditemukan di area anda.'],
            ]);
        }

        $dir = "{$kategori}/" . now()->format('Y-m');
        $label = $kategori === LaporanAlat::CATEGORY_KEHILANGAN ? 'kehilangan' : 'kerusakan';

        // Foto umum hanya diunggah bila benar-benar dipakai sebagai cadangan.
        $fotoUmum = null;
        $ambilFotoUmum = function () use ($request, $dir, $label, &$fotoUmum) {
            if ($fotoUmum === null) {
                $fotoUmum = $this->simpanFotoLaporan($request->file('foto') ?? [], $dir);
            }

            if (count($fotoUmum) < 3) {
                throw ValidationException::withMessages([
                    'foto' => ["Unggah minimal 3 foto {$label} yang valid."],
                ]);
            }

            return $fotoUmum;
        };

        $fotoPerItem = [];
        foreach (array_keys($validated['items']) as $index) {
            $berkasItem = $request->file("items.{$index}.foto") ?? [];
            $fotoPerItem[$index] = $berkasItem
                ? $this->simpanFotoLaporan($berkasItem, $dir)
                : $ambilFotoUmum();

            if (count($fotoPerItem[$index]) < 3) {
                throw ValidationException::withMessages([
                    "items.{$index}.foto" => ['Unggah minimal 3 foto untuk alat ini.'],
                ]);
            }
        }

        $tanggalLaporan = ! empty($validated['tanggal_laporan'])
            ? CarbonImmutable::parse($validated['tanggal_laporan'])->toDateString()
            : now()->toDateString();

        $laporans = DB::transaction(function () use ($validated, $alats, $kategori, $user, $targetAreaId, $fotoPerItem, $tanggalLaporan) {
            $dibuat = [];

            foreach ($validated['items'] as $index => $item) {
                $alat = $alats->get($item['alat_id']);
                $fotos = $fotoPerItem[$index];
                $fotoUtama = $fotos[0];

                $sourcePeminjamanId = null;
                if ($targetAreaId && (int) $alat->area_id !== (int) $targetAreaId) {
                    $sourcePeminjamanId = AreaAlatStock::query()
                        ->where('area_id', $targetAreaId)
                        ->where('alat_id', $alat->id)
                        ->where('active', true)
                        ->where('qty', '>', 0)
                        ->value('source_peminjaman_id');
                }

                $deskripsi = trim((string) ($item['deskripsi'] ?? ''));

                $laporan = LaporanAlat::create([
                    'kategori' => $kategori,
                    'deskripsi' => $deskripsi !== '' ? $deskripsi : ($validated['deskripsi'] ?? null),
                    'status' => LaporanAlat::STATUS_DILAPORKAN,
                    'tanggal_laporan' => $tanggalLaporan,
                    'jumlah' => $item['jumlah'],
                    'alat_id' => $alat->id,
                    'user_id' => $user->id,
                    'area_id' => $targetAreaId,
                    'source_peminjaman_id' => $sourcePeminjamanId,
                    // Kolom foto tunggal diisi foto pertama supaya tampilan lama
                    // (tautan foto pada ekspor) tetap jalan.
                    'path' => $fotoUtama['path'],
                    'original_name' => $fotoUtama['original_name'],
                    'mime' => $fotoUtama['mime'],
                    'size' => $fotoUtama['size'],
                ]);

                $laporan->photos()->createMany($fotos);

                $dibuat[] = $laporan;
            }

            return $dibuat;
        });

        return response()->json([
            'total' => count($laporans),
            'data' => collect($laporans)
                ->map(fn (LaporanAlat $laporan) => $this->mapLaporan($laporan->loadMissing(['alat.area', 'area', 'user', 'photos'])))
                ->values(),
        ], 201);
    }

    /**
     * Simpan berkas foto ke disk publik dan kembalikan datanya untuk disimpan
     * ke tabel foto laporan.
     *
     * @param  array<int, mixed>|UploadedFile  $files
     * @return array<int, array<string, mixed>>
     */
    private function simpanFotoLaporan($files, string $dir): array
    {
        return collect(is_array($files) ? $files : [$files])
            ->filter(fn ($file) => $file instanceof UploadedFile)
            ->values()
            ->map(function (UploadedFile $file, int $index) use ($dir) {
                $extension = $file->getClientOriginalExtension() ?: 'jpg';
                $filename = Str::uuid()->toString().'.'.$extension;

                return [
                    'urutan' => $index + 1,
                    'path' => $file->storeAs($dir, $filename, 'public'),
                    'original_name' => $file->getClientOriginalName(),
                    'mime' => $file->getClientMimeType(),
                    'size' => $file->getSize(),
                ];
            })
            ->all();
    }

    /**
     * Terima dua bentuk payload: daftar items[] (banyak alat) atau alat_id +
     * jumlah tunggal seperti versi sebelumnya.
     *
     * @return array<int, array{alat_id: int, jumlah: int}>
     */
    private function normalizeStoreItems(Request $request): array
    {
        $items = $request->input('items');

        if (is_array($items) && $items !== []) {
            return collect($items)
                ->map(fn ($item) => [
                    'alat_id' => is_array($item) ? (int) ($item['alat_id'] ?? $item['id'] ?? 0) : 0,
                    'jumlah' => is_array($item) ? (int) ($item['jumlah'] ?? $item['qty'] ?? 0) : 0,
                    'deskripsi' => is_array($item) ? trim((string) ($item['deskripsi'] ?? '')) : '',
                ])
                ->all();
        }

        if ($request->filled('alat_id')) {
            return [[
                'alat_id' => (int) $request->input('alat_id'),
                'jumlah' => (int) $request->input('jumlah', 1),
                'deskripsi' => '',
            ]];
        }

        return [];
    }

    /**
     * Unduh daftar alat yang bisa dilaporkan di area aktif sebagai Excel dengan
     * kolom Jumlah kosong, untuk pelaporan banyak alat sekaligus.
     */
    public function downloadDaftarAlat(Request $request, DaftarAlatExcelService $daftarAlat): BinaryFileResponse
    {
        $context = $this->resolveLaporanToolContext($request);

        $area = Area::query()->find($context['area_id']);
        $namaArea = Str::slug((string) ($area?->kode ?: $area?->name ?: 'area'));

        return $daftarAlat->unduh(
            $this->laporanToolQuery($context)
                ->orderBy('nama')
                ->get(['id', 'kode', 'nama', 'jenis_alat', 'klasifikasi_alat', 'total_aset']),
            "DAFTAR-ALAT-LAPORAN-{$namaArea}.xlsx"
        );
    }

    /**
     * Cocokkan berkas daftar alat yang diunggah dengan alat yang bisa dilaporkan,
     * tanpa menyimpan apa pun.
     */
    public function cocokkanAlat(Request $request, DaftarAlatExcelService $daftarAlat)
    {
        $context = $this->resolveLaporanToolContext($request);

        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt,xlsx', 'max:5120'],
        ]);

        return response()->json($daftarAlat->cocokkan(
            $request->file('file'),
            $this->laporanToolQuery($context)->get(['id', 'kode', 'nama', 'jenis_alat', 'total_aset']),
            'laporan'
        ));
    }

    /**
     * @return array{area_id: ?int, is_super_admin: bool}
     */
    private function resolveLaporanToolContext(Request $request): array
    {
        $user = $request->user();
        abort_unless($user, 401, 'Unauthenticated.');

        $user->loadMissing('role');
        $roleKey = strtolower((string) ($user->role?->key ?? ''));
        $isSuperAdmin = $roleKey === Role::KEY_SUPER_ADMIN;
        $bolehMelapor = in_array($roleKey, [
            Role::KEY_SP_TOOL,
            Role::KEY_PIC_TOOL,
            Role::KEY_MGR_TOOL,
            Role::KEY_ADMIN,
            Role::KEY_SUPER_ADMIN,
        ], true);

        abort_unless($bolehMelapor, 403, 'Forbidden.');

        $areaId = $this->resolveActiveAreaId($request, $roleKey, $user->area_id);
        abort_if(! $areaId && ! $isSuperAdmin, 403, 'Area tidak ditemukan.');

        return ['area_id' => $areaId, 'is_super_admin' => $isSuperAdmin];
    }

    /**
     * @param  array{area_id: ?int, is_super_admin: bool}  $context
     */
    private function laporanToolQuery(array $context): Builder
    {
        return Alat::query()->when($context['area_id'], function (Builder $query) use ($context) {
            $this->applyAccessibleToolFilter($query, (int) $context['area_id']);
        });
    }

    private function updateStatusByKategori(Request $request, LaporanAlat $laporan, string $kategori)
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        if ($laporan->kategori !== $kategori) {
            return response()->json(['message' => 'Laporan tidak ditemukan.'], 404);
        }

        $user->loadMissing('role');
        $roleKey = strtolower((string) ($user->role?->key ?? ''));
        $isSpTool = $roleKey === Role::KEY_SP_TOOL;
        $isPicTools = $roleKey === Role::KEY_PIC_TOOL;
        $isMgrTool = $roleKey === Role::KEY_MGR_TOOL;
        $isSuperAdmin = $roleKey === Role::KEY_SUPER_ADMIN;
        $isAdmin = in_array($roleKey, [Role::KEY_ADMIN, Role::KEY_SUPER_ADMIN], true);
        $targetAreaId = $this->resolveActiveAreaId($request, $roleKey, $user->area_id);

        if (! ($isSpTool || $isPicTools || $isMgrTool || $isAdmin)) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        // Status akhir laporan adalah Disetujui atau Ditolak. Laporan yang sudah
        // final masih bisa dikoreksi, tetapi hanya oleh PIC Tool, admin, dan
        // super admin supaya perubahan stok tidak bisa dilakukan sembarang role.
        $validated = $request->validate([
            'status' => ['required', 'in:' . implode(',', [
                LaporanAlat::STATUS_DILAPORKAN,
                LaporanAlat::STATUS_DISETUJUI,
                LaporanAlat::STATUS_DITOLAK,
            ])],
        ]);

        $bolehKoreksi = $isPicTools || $isAdmin;

        if (in_array((string) $laporan->status, LaporanAlat::STATUS_FINAL, true) && ! $bolehKoreksi) {
            return response()->json([
                'message' => 'Status laporan sudah final. Hubungi PIC Tool, admin, atau super admin bila ada kekeliruan.',
            ], 403);
        }

        $laporan->loadMissing('alat.area', 'area');
        $alat = $laporan->alat;
        if (! $alat) {
            return response()->json(['message' => 'Alat tidak ditemukan.'], 422);
        }

        $laporanAreaId = (int) ($laporan->area_id ?? $alat->area_id);
        if (($isSpTool || $isPicTools || $isMgrTool || (! $isSuperAdmin && $roleKey === Role::KEY_ADMIN))
            && (! $targetAreaId || $laporanAreaId !== (int) $targetAreaId)
        ) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $targetStatus = $validated['status'];

        DB::transaction(function () use ($laporan, $alat, $targetStatus) {
            $lockedLaporan = LaporanAlat::query()
                ->whereKey($laporan->id)
                ->lockForUpdate()
                ->firstOrFail();

            $lockedAlat = Alat::query()
                ->whereKey($alat->id)
                ->lockForUpdate()
                ->firstOrFail();

            $wasApproved = $lockedLaporan->status === 'Disetujui';
            $willBeApproved = $targetStatus === 'Disetujui';
            $jumlah = (int) $lockedLaporan->jumlah;

            if (! $wasApproved && $willBeApproved) {
                if ((int) $lockedAlat->total_aset < $jumlah) {
                    throw ValidationException::withMessages([
                        'status' => ['Total aset alat tidak mencukupi untuk disetujui.'],
                    ]);
                }

                $lockedAlat->decrement('total_aset', $jumlah);
                if ($lockedLaporan->area_id && (int) $lockedLaporan->area_id !== (int) $lockedAlat->area_id) {
                    $stock = AreaAlatStock::query()
                        ->where('area_id', $lockedLaporan->area_id)
                        ->where('alat_id', $lockedAlat->id)
                        ->where('active', true)
                        ->lockForUpdate()
                        ->first();
                    if ($stock) {
                        $nextQty = max((int) $stock->qty - $jumlah, 0);
                        $stock->update([
                            'qty' => $nextQty,
                            'active' => $nextQty > 0,
                        ]);
                    }
                }
            }

            if ($wasApproved && ! $willBeApproved) {
                $lockedAlat->increment('total_aset', $jumlah);
                if ($lockedLaporan->area_id && (int) $lockedLaporan->area_id !== (int) $lockedAlat->area_id) {
                    $stock = AreaAlatStock::query()
                        ->firstOrNew([
                            'area_id' => $lockedLaporan->area_id,
                            'alat_id' => $lockedAlat->id,
                            'source_peminjaman_id' => $lockedLaporan->source_peminjaman_id,
                        ]);
                    $stock->qty = (int) ($stock->qty ?? 0) + $jumlah;
                    $stock->active = true;
                    $stock->save();
                }
            }

            $lockedLaporan->update([
                'status' => $targetStatus,
            ]);
        });

        return response()->json(
            $this->mapLaporan(
                $laporan->fresh(['alat.area', 'area', 'user', 'photos'])
            )
        );
    }

    private function resolveKategoriContext(Request $request): array
    {
        $user = $request->user();
        if (! $user) {
            abort(401, 'Unauthenticated.');
        }

        $user->loadMissing('role');
        $roleKey = strtolower((string) ($user->role?->key ?? ''));
        $isGuest = $roleKey === Role::KEY_GUEST;
        $isSpTool = $roleKey === Role::KEY_SP_TOOL;
        $isPicTools = $roleKey === Role::KEY_PIC_TOOL;
        $isMgrTool = $roleKey === Role::KEY_MGR_TOOL;
        $isAdmin = in_array($roleKey, [Role::KEY_ADMIN, Role::KEY_SUPER_ADMIN], true);

        abort_unless($isGuest || $isSpTool || $isPicTools || $isMgrTool || $isAdmin, 403, 'Forbidden.');

        return [
            'role_key' => $roleKey,
            'is_guest' => $isGuest,
            'is_sp_tool' => $isSpTool,
            'is_pic_tools' => $isPicTools,
            'is_mgr_tool' => $isMgrTool,
            'is_admin' => $isAdmin,
            'area_id' => $this->resolveActiveAreaId($request, $roleKey, $user->area_id),
        ];
    }

    private function shouldReturnEmptyResult(array $context): bool
    {
        return ($context['is_guest'] || $context['is_sp_tool'] || $context['is_pic_tools'] || $context['is_mgr_tool'])
            && empty($context['area_id']);
    }

    private function buildKategoriQuery(Request $request, string $kategori, array $context, LaporanPeriod $period): Builder
    {
        $search = trim((string) $request->input('search', ''));
        $alatId = $request->input('alat_id');
        $status = trim((string) $request->input('status', ''));
        $areaId = $context['area_id'];

        $query = LaporanAlat::query()
            ->where('kategori', $kategori)
            ->with(['alat.area', 'area', 'user', 'photos'])
            ->orderByDesc('tanggal_laporan')
            ->orderByDesc('created_at')
            ->orderByDesc('id');

        $period->apply($query);

        if ($context['is_guest'] || $context['is_sp_tool'] || $context['is_pic_tools'] || $context['is_mgr_tool']) {
            $this->applyReportAreaFilter($query, $areaId);
        } elseif (! empty($areaId)) {
            $this->applyReportAreaFilter($query, $areaId);
        }

        if (! empty($alatId)) {
            $query->where('alat_id', $alatId);
        }

        if ($status !== '' && strtolower($status) !== 'semua') {
            $query->where('status', $status);
        }

        if ($search !== '') {
            $query->where(function (Builder $sub) use ($search) {
                $sub->where('deskripsi', 'like', '%' . $search . '%')
                    ->orWhere('id', $search)
                    ->orWhereHas('alat', function (Builder $alatQuery) use ($search) {
                        $alatQuery->where('nama', 'like', '%' . $search . '%');
                    })
                    ->orWhereHas('alat.area', function (Builder $areaQuery) use ($search) {
                        $areaQuery->where('name', 'like', '%' . $search . '%');
                    })
                    ->orWhereHas('user', function (Builder $userQuery) use ($search) {
                        $userQuery->where('name', 'like', '%' . $search . '%');
                    });
            });
        }

        return $query;
    }

    /**
     * Daftar foto laporan. Laporan lama yang hanya punya satu foto pada kolom
     * path tetap tampil walau belum punya baris di tabel foto.
     *
     * @return array<int, array{url: string, original_name: ?string}>
     */
    private function mapPhotos(LaporanAlat $laporan): array
    {
        $photos = $laporan->relationLoaded('photos')
            ? $laporan->photos
            : $laporan->photos()->get();

        if ($photos->isEmpty()) {
            return $laporan->path
                ? [[
                    'url' => url('/storage/' . ltrim($laporan->path, '/')),
                    'original_name' => $laporan->original_name,
                ]]
                : [];
        }

        return $photos
            ->map(fn (LaporanAlatPhoto $photo) => [
                'url' => url('/storage/' . ltrim((string) $photo->path, '/')),
                'original_name' => $photo->original_name,
            ])
            ->values()
            ->all();
    }

    private function mapLaporan(LaporanAlat $laporan): array
    {
        $alat = $laporan->alat;
        $area = $laporan->area ?: $alat?->area;
        $reporter = $laporan->user;

        return [
            'id' => $laporan->id,
            'kategori' => $laporan->kategori,
            'alat_id' => $laporan->alat_id,
            'alat_nama' => $alat?->nama ?? '-',
            'alat_kode' => $alat?->kode ?? '-',
            'area_id' => $area?->id,
            'area_name' => $area?->name ?? '-',
            'user_id' => $laporan->user_id,
            'user_name' => $reporter?->name ?? '-',
            'deskripsi' => $laporan->deskripsi,
            'jumlah' => (int) $laporan->jumlah,
            'status' => $laporan->status ?? 'Dilaporkan',
            'path' => $laporan->path,
            'url' => $laporan->path ? url('/storage/' . ltrim($laporan->path, '/')) : null,
            'photos' => $this->mapPhotos($laporan),
            'original_name' => $laporan->original_name,
            'mime' => $laporan->mime,
            'size' => $laporan->size,
            'created_at' => $laporan->created_at
                ? $laporan->created_at->format('d M Y H:i')
                : null,
            'tanggal_laporan' => $laporan->tanggal_laporan
                ? $laporan->tanggal_laporan->format('d M Y')
                : ($laporan->created_at ? $laporan->created_at->format('d M Y') : null),
            'tanggal_laporan_iso' => $laporan->tanggal_laporan
                ? $laporan->tanggal_laporan->toDateString()
                : ($laporan->created_at ? $laporan->created_at->toDateString() : null),
        ];
    }
}
