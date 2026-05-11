<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LaporanAlat;
use App\Models\Peminjaman;
use App\Models\Role;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    private array $countCache = [];
    private array $laporanCountCache = [];
    private array $latestCache = [];

    public function __invoke(Request $request)
    {
        $user = $request->user();
        if (! $user) {
            return response()->json($this->emptyPayload());
        }

        $user->loadMissing('role');
        $roleKey = strtolower((string) ($user->role?->key ?? ''));
        $areaId = $this->resolveAreaId($request, $roleKey, $user->area_id);

        $mailbox = $this->mailboxItems($roleKey, $areaId, (int) $user->id);
        $sidebar = $this->sidebarCounts($roleKey, $areaId);

        return response()->json([
            'sidebar' => $sidebar,
            'mailbox' => [
                'items' => $mailbox,
                'total' => (int) collect($mailbox)->sum('count'),
            ],
        ]);
    }

    private function emptyPayload(): array
    {
        return [
            'sidebar' => [
                'review' => 0,
                'kerusakan' => 0,
                'kehilangan' => 0,
                'pengiriman_intra_area' => 0,
                'pengiriman_antar_area' => 0,
                'mutasi_alat' => 0,
            ],
            'mailbox' => [
                'items' => [],
                'total' => 0,
            ],
        ];
    }

    private function resolveAreaId(Request $request, string $roleKey, $fallbackAreaId): ?int
    {
        if ($roleKey === Role::KEY_SUPER_ADMIN && $request->filled('area_id')) {
            return (int) $request->query('area_id');
        }

        return $fallbackAreaId ? (int) $fallbackAreaId : null;
    }

    private function peminjamanActionQuery(array $statuses): Builder
    {
        return Peminjaman::query()
            ->whereIn('status', $statuses)
            ->whereHas('items', function (Builder $sub) {
                $sub->where('approved_qty', '>', 0);
            });
    }

    private function applyKategori(Builder $query, string $kategori): Builder
    {
        if ($kategori === Peminjaman::KATEGORI_ANTAR_AREA) {
            return $query->where(function (Builder $sub) {
                $sub->where('kategori', Peminjaman::KATEGORI_ANTAR_AREA)
                    ->orWhere('is_inter_area', true);
            });
        }

        return $query
            ->where(function (Builder $sub) {
                $sub->where('kategori', Peminjaman::KATEGORI_INTRA_AREA)
                    ->orWhereNull('kategori');
            })
            ->where(function (Builder $sub) {
                $sub->where('is_inter_area', false)
                    ->orWhereNull('is_inter_area');
            });
    }

    private function sourceAreaFilter(Builder $query, ?int $areaId): Builder
    {
        return $areaId ? $query->where('area_id', $areaId) : $query;
    }

    private function requesterAreaFilter(Builder $query, ?int $areaId): Builder
    {
        return $areaId ? $query->where('requester_area_id', $areaId) : $query;
    }

    private function countSourceAction(?int $areaId, string $kategori, array $statuses): int
    {
        $cacheKey = $this->countCacheKey('source', $areaId, $kategori, $statuses);
        if (array_key_exists($cacheKey, $this->countCache)) {
            return $this->countCache[$cacheKey];
        }

        return $this->countCache[$cacheKey] = (int) $this->sourceAreaFilter(
            $this->applyKategori($this->peminjamanActionQuery($statuses), $kategori),
            $areaId
        )->count();
    }

    private function countRequesterAction(?int $areaId, array $statuses): int
    {
        $cacheKey = $this->countCacheKey('requester', $areaId, Peminjaman::KATEGORI_ANTAR_AREA, $statuses);
        if (array_key_exists($cacheKey, $this->countCache)) {
            return $this->countCache[$cacheKey];
        }

        return $this->countCache[$cacheKey] = (int) $this->requesterAreaFilter(
            $this->applyKategori($this->peminjamanActionQuery($statuses), Peminjaman::KATEGORI_ANTAR_AREA),
            $areaId
        )->count();
    }

    private function countCacheKey(string $scope, ?int $areaId, string $kategori, array $statuses): string
    {
        sort($statuses);

        return implode('|', [
            $scope,
            $areaId ?? 'all',
            $kategori,
            implode(',', $statuses),
        ]);
    }

    private function latestSourceAction(?int $areaId, string $kategori, array $statuses): string
    {
        $cacheKey = $this->countCacheKey('latest-source', $areaId, $kategori, $statuses);
        if (array_key_exists($cacheKey, $this->latestCache)) {
            return $this->latestCache[$cacheKey];
        }

        return $this->latestCache[$cacheKey] = (string) $this->sourceAreaFilter(
            $this->applyKategori($this->peminjamanActionQuery($statuses), $kategori),
            $areaId
        )->max('updated_at');
    }

    private function latestRequesterAction(?int $areaId, array $statuses): string
    {
        $cacheKey = $this->countCacheKey('latest-requester', $areaId, Peminjaman::KATEGORI_ANTAR_AREA, $statuses);
        if (array_key_exists($cacheKey, $this->latestCache)) {
            return $this->latestCache[$cacheKey];
        }

        return $this->latestCache[$cacheKey] = (string) $this->requesterAreaFilter(
            $this->applyKategori($this->peminjamanActionQuery($statuses), Peminjaman::KATEGORI_ANTAR_AREA),
            $areaId
        )->max('updated_at');
    }

    private function sidebarCounts(string $roleKey, ?int $areaId): array
    {
        $sidebar = $this->emptyPayload()['sidebar'];

        if (in_array($roleKey, [Role::KEY_SP_TOOL, Role::KEY_MGR_TOOL, Role::KEY_SUPER_ADMIN], true)) {
            $sidebar['review'] = $this->reviewCount($areaId);
        }

        if (in_array($roleKey, [Role::KEY_SP_TOOL, Role::KEY_PIC_TOOLS, 'pic_tool', Role::KEY_MGR_TOOL, Role::KEY_ADMIN, Role::KEY_SUPER_ADMIN], true)) {
            ['kerusakan' => $sidebar['kerusakan'], 'kehilangan' => $sidebar['kehilangan']] = $this->laporanCounts($roleKey, $areaId);
        }

        if (in_array($roleKey, [Role::KEY_PIC_TOOLS, 'pic_tool', Role::KEY_ADMIN, Role::KEY_SUPER_ADMIN], true)) {
            $sidebar['pengiriman_intra_area'] = $this->shippingSidebarCount($areaId, Peminjaman::KATEGORI_INTRA_AREA);
            $sidebar['pengiriman_antar_area'] = $this->shippingSidebarCount(
                $areaId,
                Peminjaman::KATEGORI_ANTAR_AREA,
                $roleKey !== Role::KEY_SUPER_ADMIN
            );
        }

        if (in_array($roleKey, [Role::KEY_MGR_TOOL, Role::KEY_SUPER_ADMIN], true)) {
            $sidebar['mutasi_alat'] = $this->mutasiActionCount($areaId, [
                Peminjaman::STATUS_DIKIRIM,
                Peminjaman::STATUS_DITERIMA,
                Peminjaman::STATUS_DIKEMBALIKAN_PARTIALS,
            ]);
        }

        return $sidebar;
    }

    private function shippingSidebarCount(?int $areaId, string $kategori, bool $includeRequesterActions = true): int
    {
        if ($kategori === Peminjaman::KATEGORI_ANTAR_AREA) {
            $sourceCount = $this->countSourceAction($areaId, $kategori, [
                Peminjaman::STATUS_DIPESAN,
                Peminjaman::STATUS_DIKEMBALIKAN_SEMUANYA,
            ]);

            if (! $includeRequesterActions) {
                return $sourceCount;
            }

            return $sourceCount + $this->countRequesterAction($areaId, [
                    Peminjaman::STATUS_DIKIRIM,
                    Peminjaman::STATUS_DITERIMA,
                    Peminjaman::STATUS_DIKEMBALIKAN_PARTIALS,
                ]);
        }

        $shipping = $this->countSourceAction($areaId, $kategori, [
            Peminjaman::STATUS_DIPESAN,
            Peminjaman::STATUS_DIKIRIM,
        ]);

        $returns = $this->countSourceAction($areaId, $kategori, [
            Peminjaman::STATUS_DIKEMBALIKAN_PARTIALS,
            Peminjaman::STATUS_DIKEMBALIKAN_SEMUANYA,
        ]);

        return $shipping + $returns;
    }

    private function mutasiActionQuery(?int $areaId, array $statuses): Builder
    {
        $query = $this->peminjamanActionQuery($statuses);

        if (! $areaId) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(function (Builder $sub) use ($areaId) {
            $sub->where(function (Builder $intra) use ($areaId) {
                $this->applyKategori($intra, Peminjaman::KATEGORI_INTRA_AREA)
                    ->where('area_id', $areaId);
            })->orWhere(function (Builder $antar) use ($areaId) {
                $this->applyKategori($antar, Peminjaman::KATEGORI_ANTAR_AREA)
                    ->where('requester_area_id', $areaId);
            });
        });
    }

    private function mutasiActionCount(?int $areaId, array $statuses): int
    {
        $cacheKey = $this->countCacheKey('mutasi', $areaId, 'mutasi-alat', $statuses);
        if (array_key_exists($cacheKey, $this->countCache)) {
            return $this->countCache[$cacheKey];
        }

        return $this->countCache[$cacheKey] = (int) $this->mutasiActionQuery($areaId, $statuses)->count();
    }

    private function latestMutasiAction(?int $areaId, array $statuses): string
    {
        $cacheKey = $this->countCacheKey('latest-mutasi', $areaId, 'mutasi-alat', $statuses);
        if (array_key_exists($cacheKey, $this->latestCache)) {
            return $this->latestCache[$cacheKey];
        }

        return $this->latestCache[$cacheKey] = (string) $this->mutasiActionQuery($areaId, $statuses)->max('updated_at');
    }

    private function reviewCount(?int $areaId): int
    {
        if (! $areaId) {
            return 0;
        }

        return (int) Peminjaman::query()
            ->where(function (Builder $sub) use ($areaId) {
                $sub->where(function (Builder $sourceQuery) use ($areaId) {
                    $sourceQuery
                        ->where('area_id', $areaId)
                        ->where('status', Peminjaman::STATUS_MENUNGGU_REVIEW);
                })->orWhere(function (Builder $requesterQuery) use ($areaId) {
                    $requesterQuery
                        ->where('is_inter_area', true)
                        ->where('requester_area_id', $areaId)
                        ->where('status', Peminjaman::STATUS_MENUNGGU_REVIEW_AREA_PEMINJAM);
                });
            })
            ->count();
    }

    private function laporanCounts(string $roleKey, ?int $areaId): array
    {
        $cacheKey = implode('|', ['laporan', $roleKey, $areaId ?? 'all']);
        if (array_key_exists($cacheKey, $this->laporanCountCache)) {
            return $this->laporanCountCache[$cacheKey];
        }

        $query = LaporanAlat::query()->where('status', 'Dilaporkan');
        $shouldFilterArea = in_array($roleKey, [Role::KEY_SP_TOOL, Role::KEY_PIC_TOOLS, 'pic_tool', Role::KEY_MGR_TOOL], true)
            || ! empty($areaId);

        if ($shouldFilterArea) {
            if (! $areaId) {
                return $this->laporanCountCache[$cacheKey] = ['kerusakan' => 0, 'kehilangan' => 0];
            }

            $query->where('area_id', $areaId);
        }

        $counts = $query
            ->selectRaw('kategori, COUNT(*) as total')
            ->groupBy('kategori')
            ->pluck('total', 'kategori');

        return $this->laporanCountCache[$cacheKey] = [
            'kerusakan' => (int) ($counts[LaporanAlat::CATEGORY_KERUSAKAN] ?? 0),
            'kehilangan' => (int) ($counts[LaporanAlat::CATEGORY_KEHILANGAN] ?? 0),
        ];
    }

    private function mailboxItems(string $roleKey, ?int $areaId, int $userId): array
    {
        $items = match (true) {
            $roleKey === Role::KEY_SUPER_ADMIN => [
                ...$this->picToolMailboxItems($areaId, false),
                ...$this->reviewMailboxItems($areaId),
                ...$this->mutasiMailboxItems($areaId),
                ...$this->laporanMailboxItems($roleKey, $areaId),
            ],
            in_array($roleKey, [Role::KEY_PIC_TOOLS, 'pic_tool', Role::KEY_ADMIN], true) => [
                ...$this->picToolMailboxItems($areaId),
                ...$this->laporanMailboxItems($roleKey, $areaId),
            ],
            in_array($roleKey, [Role::KEY_SP_TOOL, Role::KEY_MGR_TOOL], true) => [
                ...$this->reviewMailboxItems($areaId),
                ...($roleKey === Role::KEY_MGR_TOOL ? $this->mutasiMailboxItems($areaId) : []),
                ...$this->laporanMailboxItems($roleKey, $areaId),
            ],
            $roleKey === Role::KEY_USER => $this->userMailboxItems($userId),
            default => [],
        };

        return collect($items)
            ->filter(fn (array $item) => (int) ($item['count'] ?? 0) > 0)
            ->sortByDesc(fn (array $item) => $item['sort_at'] ?? '')
            ->map(function (array $item) {
                unset($item['sort_at']);

                return $item;
            })
            ->values()
            ->all();
    }

    private function laporanMailboxItems(string $roleKey, ?int $areaId): array
    {
        $counts = $this->laporanCounts($roleKey, $areaId);

        return [
            [
                'key' => 'laporan-kerusakan',
                'title' => 'Laporan - Kerusakan - Dilaporkan',
                'description' => 'Perlu ditindaklanjuti',
                'count' => $counts['kerusakan'],
                'href' => '/laporan-kerusakan',
                'sort_at' => $this->latestLaporanAt($roleKey, $areaId, LaporanAlat::CATEGORY_KERUSAKAN),
            ],
            [
                'key' => 'laporan-kehilangan',
                'title' => 'Laporan - Kehilangan - Dilaporkan',
                'description' => 'Perlu ditindaklanjuti',
                'count' => $counts['kehilangan'],
                'href' => '/laporan-kehilangan',
                'sort_at' => $this->latestLaporanAt($roleKey, $areaId, LaporanAlat::CATEGORY_KEHILANGAN),
            ],
        ];
    }

    private function latestLaporanAt(string $roleKey, ?int $areaId, string $kategori): string
    {
        $cacheKey = implode('|', ['latest-laporan', $roleKey, $areaId ?? 'all', $kategori]);
        if (array_key_exists($cacheKey, $this->latestCache)) {
            return $this->latestCache[$cacheKey];
        }

        $query = LaporanAlat::query()
            ->where('status', 'Dilaporkan')
            ->where('kategori', $kategori);
        $shouldFilterArea = in_array($roleKey, [Role::KEY_SP_TOOL, Role::KEY_PIC_TOOLS, 'pic_tool', Role::KEY_MGR_TOOL], true)
            || ! empty($areaId);

        if ($shouldFilterArea) {
            if (! $areaId) {
                return $this->latestCache[$cacheKey] = '';
            }

            $query->where('area_id', $areaId);
        }

        return $this->latestCache[$cacheKey] = (string) $query->max('updated_at');
    }

    private function picToolMailboxItems(?int $areaId, bool $includeRequesterActions = true): array
    {
        $items = [
            [
                'key' => 'pengiriman-intra-siap',
                'title' => 'Peminjaman - Intra Area - Siap Dikirim',
                'description' => 'Perlu diproses pengiriman',
                'count' => $this->countSourceAction($areaId, Peminjaman::KATEGORI_INTRA_AREA, [Peminjaman::STATUS_DIPESAN]),
                'href' => '/pengiriman-alat?tab=siap-dikirim',
                'sort_at' => $this->latestSourceAction($areaId, Peminjaman::KATEGORI_INTRA_AREA, [Peminjaman::STATUS_DIPESAN]),
            ],
            [
                'key' => 'pengiriman-antar-siap',
                'title' => 'Peminjaman - Antar Area - Siap Dikirim',
                'description' => 'Perlu diproses pengiriman',
                'count' => $this->countSourceAction($areaId, Peminjaman::KATEGORI_ANTAR_AREA, [Peminjaman::STATUS_DIPESAN]),
                'href' => '/pengiriman-antar-area?tab=siap-dikirim',
                'sort_at' => $this->latestSourceAction($areaId, Peminjaman::KATEGORI_ANTAR_AREA, [Peminjaman::STATUS_DIPESAN]),
            ],
            [
                'key' => 'penyelesaian-antar-kembali',
                'title' => 'Peminjaman - Antar Area - Dikembalikan Semua',
                'description' => 'Perlu diselesaikan area sumber',
                'count' => $this->countSourceAction($areaId, Peminjaman::KATEGORI_ANTAR_AREA, [Peminjaman::STATUS_DIKEMBALIKAN_SEMUANYA]),
                'href' => '/pengiriman-antar-area?tab=dikembalikan-semua',
                'sort_at' => $this->latestSourceAction($areaId, Peminjaman::KATEGORI_ANTAR_AREA, [Peminjaman::STATUS_DIKEMBALIKAN_SEMUANYA]),
            ],
        ];

        if ($includeRequesterActions) {
            array_splice($items, 2, 0, [
                [
                    'key' => 'penerimaan-antar-dikirim',
                    'title' => 'Peminjaman - Antar Area - Dikirim',
                    'description' => 'Perlu diterima area peminjam',
                    'count' => $this->countRequesterAction($areaId, [Peminjaman::STATUS_DIKIRIM]),
                    'href' => '/pengiriman-antar-area?tab=dikirim',
                    'sort_at' => $this->latestRequesterAction($areaId, [Peminjaman::STATUS_DIKIRIM]),
                ],
                [
                    'key' => 'pengembalian-antar-diterima',
                    'title' => 'Peminjaman - Antar Area - Diterima',
                    'description' => 'Perlu dikembalikan area peminjam',
                    'count' => $this->countRequesterAction($areaId, [Peminjaman::STATUS_DITERIMA, Peminjaman::STATUS_DIKEMBALIKAN_PARTIALS]),
                    'href' => '/pengiriman-antar-area?tab=diterima',
                    'sort_at' => $this->latestRequesterAction($areaId, [Peminjaman::STATUS_DITERIMA, Peminjaman::STATUS_DIKEMBALIKAN_PARTIALS]),
                ],
            ]);
        }

        return $items;
    }

    private function mutasiMailboxItems(?int $areaId): array
    {
        return [
            [
                'key' => 'mutasi-penerimaan',
                'title' => 'Mutasi Alat - Dikirim',
                'description' => 'Perlu diterima area peminjam',
                'count' => $this->mutasiActionCount($areaId, [Peminjaman::STATUS_DIKIRIM]),
                'href' => '/mutasi-alat?tab=dikirim',
                'sort_at' => $this->latestMutasiAction($areaId, [Peminjaman::STATUS_DIKIRIM]),
            ],
            [
                'key' => 'mutasi-pengembalian',
                'title' => 'Mutasi Alat - Diterima',
                'description' => 'Perlu dikembalikan area peminjam',
                'count' => $this->mutasiActionCount($areaId, [Peminjaman::STATUS_DITERIMA, Peminjaman::STATUS_DIKEMBALIKAN_PARTIALS]),
                'href' => '/mutasi-alat?tab=diterima',
                'sort_at' => $this->latestMutasiAction($areaId, [Peminjaman::STATUS_DITERIMA, Peminjaman::STATUS_DIKEMBALIKAN_PARTIALS]),
            ],
        ];
    }

    private function reviewMailboxItems(?int $areaId): array
    {
        if (! $areaId) {
            return [];
        }

        $intraCount = (int) $this->sourceAreaFilter(
            $this->applyKategori(
                Peminjaman::query()->where('status', Peminjaman::STATUS_MENUNGGU_REVIEW),
                Peminjaman::KATEGORI_INTRA_AREA
            ),
            $areaId
        )->count();

        $antarCount = (int) Peminjaman::query()
            ->where(function (Builder $sub) use ($areaId) {
                $sub->where(function (Builder $requesterQuery) use ($areaId) {
                    $requesterQuery
                        ->where('requester_area_id', $areaId)
                        ->where('status', Peminjaman::STATUS_MENUNGGU_REVIEW_AREA_PEMINJAM);
                })->orWhere(function (Builder $sourceQuery) use ($areaId) {
                    $sourceQuery
                        ->where('area_id', $areaId)
                        ->where('status', Peminjaman::STATUS_MENUNGGU_REVIEW);
                });
            })
            ->where(function (Builder $sub) {
                $sub->where('kategori', Peminjaman::KATEGORI_ANTAR_AREA)
                    ->orWhere('is_inter_area', true);
            })
            ->count();

        return [
            [
                'key' => 'review-peminjaman-intra',
                'title' => 'Peminjaman - Intra Area - Perlu Persetujuan',
                'description' => 'Perlu review/persetujuan peminjaman intra area',
                'count' => $intraCount,
                'href' => '/review-peminjaman',
                'sort_at' => $this->latestSourceAction($areaId, Peminjaman::KATEGORI_INTRA_AREA, [Peminjaman::STATUS_MENUNGGU_REVIEW]),
            ],
            [
                'key' => 'review-peminjaman-antar',
                'title' => 'Peminjaman - Antar Area - Perlu Persetujuan',
                'description' => 'Perlu review/persetujuan peminjaman antar area',
                'count' => $antarCount,
                'href' => '/review-peminjaman',
                'sort_at' => $this->latestReviewAntarAt($areaId),
            ],
        ];
    }

    private function latestReviewAntarAt(?int $areaId): string
    {
        if (! $areaId) {
            return '';
        }

        $cacheKey = implode('|', ['latest-review-antar', $areaId]);
        if (array_key_exists($cacheKey, $this->latestCache)) {
            return $this->latestCache[$cacheKey];
        }

        return $this->latestCache[$cacheKey] = (string) Peminjaman::query()
            ->where(function (Builder $sub) use ($areaId) {
                $sub->where(function (Builder $requesterQuery) use ($areaId) {
                    $requesterQuery
                        ->where('requester_area_id', $areaId)
                        ->where('status', Peminjaman::STATUS_MENUNGGU_REVIEW_AREA_PEMINJAM);
                })->orWhere(function (Builder $sourceQuery) use ($areaId) {
                    $sourceQuery
                        ->where('area_id', $areaId)
                        ->where('status', Peminjaman::STATUS_MENUNGGU_REVIEW);
                });
            })
            ->where(function (Builder $sub) {
                $sub->where('kategori', Peminjaman::KATEGORI_ANTAR_AREA)
                    ->orWhere('is_inter_area', true);
            })
            ->max('updated_at');
    }

    private function userMailboxItems(int $userId): array
    {
        return [
            [
                'key' => 'user-penerimaan',
                'title' => 'Peminjaman - Intra Area - Dikirim',
                'description' => 'Perlu diterima',
                'count' => (int) $this->applyKategori(
                    $this->peminjamanActionQuery([Peminjaman::STATUS_DIKIRIM])->where('user_id', $userId),
                    Peminjaman::KATEGORI_INTRA_AREA
                )->count(),
                'href' => '/mutasi-alat?tab=dikirim',
                'sort_at' => $this->latestUserAction($userId, [Peminjaman::STATUS_DIKIRIM]),
            ],
            [
                'key' => 'user-pengembalian',
                'title' => 'Peminjaman - Intra Area - Diterima',
                'description' => 'Perlu dikembalikan',
                'count' => (int) $this->applyKategori(
                    $this->peminjamanActionQuery([Peminjaman::STATUS_DITERIMA, Peminjaman::STATUS_DIKEMBALIKAN_PARTIALS])->where('user_id', $userId),
                    Peminjaman::KATEGORI_INTRA_AREA
                )->count(),
                'href' => '/mutasi-alat?tab=diterima',
                'sort_at' => $this->latestUserAction($userId, [Peminjaman::STATUS_DITERIMA, Peminjaman::STATUS_DIKEMBALIKAN_PARTIALS]),
            ],
        ];
    }

    private function latestUserAction(int $userId, array $statuses): string
    {
        $cacheKey = $this->countCacheKey("latest-user-{$userId}", null, Peminjaman::KATEGORI_INTRA_AREA, $statuses);
        if (array_key_exists($cacheKey, $this->latestCache)) {
            return $this->latestCache[$cacheKey];
        }

        return $this->latestCache[$cacheKey] = (string) $this->applyKategori(
            $this->peminjamanActionQuery($statuses)->where('user_id', $userId),
            Peminjaman::KATEGORI_INTRA_AREA
        )->max('updated_at');
    }
}
