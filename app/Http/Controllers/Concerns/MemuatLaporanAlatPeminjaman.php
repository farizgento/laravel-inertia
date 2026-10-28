<?php

namespace App\Http\Controllers\Concerns;

use App\Models\LaporanAlat;
use App\Models\Peminjaman;
use App\Models\PeminjamanItem;
use Illuminate\Support\Collection;

/**
 * Laporan kerusakan/kehilangan yang menempel pada tiap peminjaman.
 *
 * Sebelumnya tiap peminjaman menjalankan satu query sendiri, sehingga daftar
 * berisi 150 peminjaman menghasilkan 150 query tambahan. Di sini seluruh
 * laporan untuk satu halaman diambil sekali, lalu dibagikan per peminjaman di
 * PHP. Jumlah query jadi tetap, tidak lagi mengikuti banyaknya baris.
 */
trait MemuatLaporanAlatPeminjaman
{
    /**
     * Laporan per peminjaman, sudah dikelompokkan per alat.
     *
     * @var array<int, Collection>
     */
    private array $laporanAlatTerprefetch = [];

    /**
     * @param  Collection<int, Peminjaman>  $peminjamans
     */
    private function prefetchLaporanAlat(Collection $peminjamans): void
    {
        $this->laporanAlatTerprefetch = [];

        if ($peminjamans->isEmpty()) {
            return;
        }

        $userIds = $peminjamans->pluck('user_id')->filter()->unique()->values();
        $alatIds = $peminjamans
            ->flatMap(fn (Peminjaman $peminjaman) => $peminjaman->items->pluck('alat_id'))
            ->filter()
            ->unique()
            ->values();

        if ($userIds->isEmpty() || $alatIds->isEmpty()) {
            return;
        }

        // Rentang waktu dipersempit ke gabungan rentang seluruh peminjaman di
        // halaman ini, supaya query tidak memindai seluruh riwayat laporan.
        $awal = $peminjamans->pluck('created_at')->filter()->min();
        $akhir = $peminjamans->pluck('updated_at')->filter()->max();

        $laporans = LaporanAlat::query()
            ->with('alat.area')
            ->whereIn('user_id', $userIds->all())
            ->whereIn('alat_id', $alatIds->all())
            ->when($awal && $akhir, fn ($query) => $query->whereBetween('created_at', [$awal, $akhir]))
            ->orderByDesc('created_at')
            ->get()
            ->groupBy('user_id');

        foreach ($peminjamans as $peminjaman) {
            $milikPengguna = $laporans->get($peminjaman->user_id, collect());

            $this->laporanAlatTerprefetch[$peminjaman->id] = $milikPengguna
                ->filter(function (LaporanAlat $laporan) use ($peminjaman) {
                    if (! $peminjaman->created_at || ! $peminjaman->updated_at) {
                        return true;
                    }

                    return $laporan->created_at
                        && $laporan->created_at->betweenIncluded($peminjaman->created_at, $peminjaman->updated_at);
                })
                ->groupBy('alat_id');
        }
    }

    /**
     * Dipakai formatter. Bila halaman belum di-prefetch (misalnya pemanggilan
     * untuk satu peminjaman saja), query tunggal tetap dijalankan.
     */
    private function laporanAlatUntuk(Peminjaman $peminjaman): Collection
    {
        if (array_key_exists($peminjaman->id, $this->laporanAlatTerprefetch)) {
            return $this->laporanAlatTerprefetch[$peminjaman->id];
        }

        $alatIds = $peminjaman->items
            ->pluck('alat_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->values();

        if ($alatIds->isEmpty()) {
            return collect();
        }

        return LaporanAlat::query()
            ->with('alat.area')
            ->where('user_id', $peminjaman->user_id)
            ->whereIn('alat_id', $alatIds->all())
            ->when(
                $peminjaman->created_at && $peminjaman->updated_at,
                fn ($query) => $query->whereBetween('created_at', [
                    $peminjaman->created_at,
                    $peminjaman->updated_at,
                ])
            )
            ->orderByDesc('created_at')
            ->get()
            ->groupBy('alat_id');
    }
}
