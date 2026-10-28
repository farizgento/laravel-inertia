<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * Bentuk respons daftar yang seragam untuk endpoint peminjaman.
 *
 * Sebelumnya endpoint-endpoint ini mengambil seluruh baris sekaligus, sehingga
 * bebannya tumbuh terus seiring data bertambah. Sekarang dibatasi per halaman,
 * dengan "meta.has_more" supaya antarmuka tahu masih ada sisa data.
 */
trait MembuatDaftarBerhalaman
{
    private const PER_PAGE_DEFAULT = 25;

    private const PER_PAGE_MAKS = 100;

    /**
     * @param  callable(Collection): Collection  $format  Mengubah koleksi model menjadi baris siap kirim.
     * @return array{data: array<int, mixed>, meta: array<string, mixed>}
     */
    /**
     * @param  array<string, mixed>  $tambahan  Isi tambahan pada respons, misalnya rekap per status.
     */
    private function daftarBerhalaman(Builder $query, Request $request, callable $format, array $tambahan = []): array
    {
        $halaman = $query->paginate($this->perPage($request));

        return array_merge([
            'data' => $format($halaman->getCollection())->values()->all(),
            'meta' => $this->metaHalaman($halaman),
        ], $tambahan);
    }

    private function perPage(Request $request): int
    {
        $perPage = (int) $request->query('per_page', self::PER_PAGE_DEFAULT);

        return $perPage > 0 ? min($perPage, self::PER_PAGE_MAKS) : self::PER_PAGE_DEFAULT;
    }

    /**
     * @return array<string, mixed>
     */
    private function metaHalaman(LengthAwarePaginator $halaman): array
    {
        return [
            'current_page' => $halaman->currentPage(),
            'last_page' => $halaman->lastPage(),
            'per_page' => $halaman->perPage(),
            'total' => $halaman->total(),
            'has_more' => $halaman->currentPage() < $halaman->lastPage(),
        ];
    }
}
