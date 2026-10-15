<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PekerjaanOh;
use App\Models\Role;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PekerjaanOhController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->query('search', ''));
        $jenis = trim((string) $request->query('jenis', ''));

        $query = PekerjaanOh::query()
            ->with('creator')
            ->orderBy('tanggal_mulai')
            ->orderBy('id');

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
    private function buildRingkasan($query): array
    {
        $hariIni = CarbonImmutable::now()->startOfDay();
        $ringkasan = ['total' => 0, 'akan_datang' => 0, 'berlangsung' => 0, 'selesai' => 0];

        $query->reorder()->get(['tanggal_mulai', 'tanggal_selesai'])->each(function ($baris) use (&$ringkasan, $hariIni) {
            $ringkasan['total']++;
            $mulai = $baris->tanggal_mulai;
            $selesai = $baris->tanggal_selesai;

            if ($mulai && $mulai->startOfDay()->greaterThan($hariIni)) {
                $ringkasan['akan_datang']++;

                return;
            }

            if ($selesai && $selesai->startOfDay()->lessThan($hariIni)) {
                $ringkasan['selesai']++;

                return;
            }

            $ringkasan['berlangsung']++;
        });

        return $ringkasan;
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $this->validatePayload($request);

        $pekerjaan = PekerjaanOh::create($validated + [
            'created_by' => $request->user()?->id,
        ]);

        return response()->json([
            'message' => 'Pekerjaan overhaul berhasil ditambahkan.',
            'data' => $this->format($pekerjaan->load('creator')),
        ], 201);
    }

    public function update(Request $request, PekerjaanOh $pekerjaanOh): JsonResponse
    {
        $pekerjaanOh->update($this->validatePayload($request));

        return response()->json([
            'message' => 'Pekerjaan overhaul berhasil diperbarui.',
            'data' => $this->format($pekerjaanOh->fresh('creator')),
        ]);
    }

    public function destroy(PekerjaanOh $pekerjaanOh): JsonResponse
    {
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
