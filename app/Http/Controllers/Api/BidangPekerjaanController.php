<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BidangPekerjaan;
use App\Services\ActivityLogger;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Throwable;

class BidangPekerjaanController extends Controller
{
    /**
     * Pesan ditulis sendiri karena aplikasi memakai bahasa Indonesia sementara
     * pesan bawaan Laravel masih berbahasa Inggris.
     */
    private const PESAN_VALIDASI = [
        'nama.required' => 'Nama bidang wajib diisi.',
        'nama.unique' => 'Nama bidang sudah digunakan.',
        'nama.max' => 'Nama bidang maksimal 255 karakter.',
    ];

    public function index(Request $request): array
    {
        $search = trim((string) $request->query('search', ''));

        $query = BidangPekerjaan::query()
            ->withCount('peminjamans')
            ->orderBy('nama');

        if ($search !== '') {
            $query->whereRaw('LOWER(nama) LIKE ?', ['%'.mb_strtolower($search).'%']);
        }

        $perPage = (int) $request->query('per_page', 10);
        $perPage = $perPage > 0 ? min($perPage, 100) : 10;
        $rows = $query->paginate($perPage);

        return [
            'data' => $rows->getCollection()
                ->map(fn (BidangPekerjaan $bidang) => $this->format($bidang))
                ->values(),
            'meta' => [
                'current_page' => $rows->currentPage(),
                'last_page' => $rows->lastPage(),
                'per_page' => $rows->perPage(),
                'total' => $rows->total(),
            ],
        ];
    }

    /**
     * Daftar untuk pilihan bidang di form peminjaman. Terbuka untuk semua
     * pengguna yang sudah login karena peminjam hanya memilih.
     */
    public function pilihan(): array
    {
        return [
            'data' => BidangPekerjaan::query()
                ->orderBy('nama')
                ->get(['id', 'nama'])
                ->map(fn (BidangPekerjaan $bidang) => [
                    'id' => $bidang->id,
                    'nama' => $bidang->nama,
                ])
                ->values(),
        ];
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:255', Rule::unique('bidang_pekerjaans', 'nama')],
        ], self::PESAN_VALIDASI);

        $bidang = BidangPekerjaan::create(['nama' => trim($validated['nama'])]);

        $this->catatAktivitas($request, 'create', $bidang, 'Menambah bidang pekerjaan '.$bidang->nama.'.');

        return response()->json([
            'message' => 'Bidang pekerjaan berhasil ditambahkan.',
            'bidang' => $this->format($bidang->loadCount('peminjamans')),
        ], 201);
    }

    public function update(Request $request, BidangPekerjaan $bidangPekerjaan): JsonResponse
    {
        $validated = $request->validate([
            'nama' => [
                'required',
                'string',
                'max:255',
                Rule::unique('bidang_pekerjaans', 'nama')->ignore($bidangPekerjaan->id),
            ],
        ], self::PESAN_VALIDASI);

        $namaLama = $bidangPekerjaan->nama;
        $bidangPekerjaan->update(['nama' => trim($validated['nama'])]);

        $this->catatAktivitas(
            $request,
            'update',
            $bidangPekerjaan,
            'Mengubah bidang pekerjaan '.$namaLama.' menjadi '.$bidangPekerjaan->nama.'.'
        );

        return response()->json([
            'message' => 'Bidang pekerjaan berhasil diperbarui.',
            'bidang' => $this->format($bidangPekerjaan->fresh()->loadCount('peminjamans')),
        ]);
    }

    public function destroy(Request $request, BidangPekerjaan $bidangPekerjaan): JsonResponse
    {
        // Menghapus bidang yang masih dipakai akan mengosongkan bidang pada
        // peminjaman terkait, jadi ditolak lebih dulu agar datanya tidak rusak.
        if ($bidangPekerjaan->peminjamans()->exists()) {
            return response()->json([
                'message' => 'Bidang pekerjaan tidak dapat dihapus karena masih dipakai peminjaman.',
            ], 422);
        }

        $nama = $bidangPekerjaan->nama;

        try {
            $bidangPekerjaan->delete();
        } catch (QueryException $exception) {
            return response()->json([
                'message' => 'Bidang pekerjaan tidak dapat dihapus karena masih terhubung dengan data lain.',
            ], 422);
        }

        $this->catatAktivitas($request, 'delete', $bidangPekerjaan, 'Menghapus bidang pekerjaan '.$nama.'.');

        return response()->json([
            'message' => 'Bidang pekerjaan berhasil dihapus.',
        ]);
    }

    private function format(BidangPekerjaan $bidang): array
    {
        return [
            'id' => $bidang->id,
            'nama' => $bidang->nama,
            'jumlah_peminjaman' => (int) ($bidang->peminjamans_count ?? 0),
            'created_at' => $bidang->created_at?->format('d/m/Y H:i'),
        ];
    }

    /**
     * Pencatatan aktivitas tidak boleh menggagalkan aksinya, sama seperti pada
     * proses login.
     */
    private function catatAktivitas(Request $request, string $aksi, BidangPekerjaan $bidang, string $deskripsi): void
    {
        try {
            $user = $request->user();

            ActivityLogger::log($aksi, $bidang, [
                'actor' => $user,
                'subject_type' => 'Bidang Pekerjaan',
                'subject_label' => $bidang->nama,
                'area_id' => $user?->area_id,
                'description' => $deskripsi,
            ]);
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}
