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
    private array $actionStatsCache = [];
    private array $laporanStatsCache = [];
    private array $reviewStatsCache = [];
    private array $userActionStatsCache = [];

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
        if (in_array($roleKey, [Role::KEY_GUEST, Role::KEY_SUPER_ADMIN], true) && $request->filled('area_id')) {
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
        return $this->sumActionRows(
            $this->actionStats($areaId),
            fn (array $row) => $this->matchesStatus($row, $statuses)
                && $this->matchesKategori($row, $kategori)
                && $this->matchesArea($row['area_id'], $areaId)
        );
    }

    private function countRequesterAction(?int $areaId, array $statuses): int
    {
        return $this->sumActionRows(
            $this->actionStats($areaId),
            fn (array $row) => $this->matchesStatus($row, $statuses)
                && $this->matchesKategori($row, Peminjaman::KATEGORI_ANTAR_AREA)
                && $this->matchesArea($row['requester_area_id'], $areaId)
        );
    }

    private function latestSourceAction(?int $areaId, string $kategori, array $statuses): string
    {
        return $this->latestActionRows(
            $this->actionStats($areaId),
            fn (array $row) => $this->matchesStatus($row, $statuses)
                && $this->matchesKategori($row, $kategori)
                && $this->matchesArea($row['area_id'], $areaId)
        );
    }

    private function latestRequesterAction(?int $areaId, array $statuses): string
    {
        return $this->latestActionRows(
            $this->actionStats($areaId),
            fn (array $row) => $this->matchesStatus($row, $statuses)
                && $this->matchesKategori($row, Peminjaman::KATEGORI_ANTAR_AREA)
                && $this->matchesArea($row['requester_area_id'], $areaId)
        );
    }

    private function actionStats(?int $areaId): array
    {
        $cacheKey = $areaId ? "area:{$areaId}" : 'all';
        if (array_key_exists($cacheKey, $this->actionStatsCache)) {
            return $this->actionStatsCache[$cacheKey];
        }

        $statuses = array_values(array_unique([
            Peminjaman::STATUS_MENUNGGU_REVIEW,
            Peminjaman::STATUS_DISETUJUI,
            Peminjaman::STATUS_DIKIRIM,
            Peminjaman::STATUS_DITERIMA,
            Peminjaman::STATUS_DIKEMBALIKAN_PARTIALS,
            Peminjaman::STATUS_DIKEMBALIKAN_SEMUANYA,
        ]));

        return $this->actionStatsCache[$cacheKey] = $this->peminjamanActionQuery($statuses)
            ->when($areaId, function (Builder $query) use ($areaId) {
                $query->where(function (Builder $areaQuery) use ($areaId) {
                    $areaQuery
                        ->where('area_id', $areaId)
                        ->orWhere('requester_area_id', $areaId);
                });
            })
            ->selectRaw('status, kategori, is_inter_area, area_id, requester_area_id, COUNT(*) as total, MAX(updated_at) as latest_at')
            ->groupBy('status', 'kategori', 'is_inter_area', 'area_id', 'requester_area_id')
            ->get()
            ->map(fn ($row) => [
                'status' => (string) $row->status,
                'kategori' => $row->kategori,
                'is_inter_area' => $this->toBool($row->is_inter_area),
                'area_id' => $this->normalizeAreaId($row->area_id),
                'requester_area_id' => $this->normalizeAreaId($row->requester_area_id),
                'total' => (int) $row->total,
                'latest_at' => (string) ($row->latest_at ?? ''),
            ])
            ->all();
    }

    private function matchesStatus(array $row, array $statuses): bool
    {
        return in_array($row['status'], $statuses, true);
    }

    private function matchesKategori(array $row, string $kategori): bool
    {
        $rowKategori = (string) ($row['kategori'] ?? '');

        if ($kategori === Peminjaman::KATEGORI_ANTAR_AREA) {
            return $rowKategori === Peminjaman::KATEGORI_ANTAR_AREA || $row['is_inter_area'] === true;
        }

        return in_array($rowKategori, [Peminjaman::KATEGORI_INTRA_AREA, ''], true)
            && $row['is_inter_area'] === false;
    }

    private function matchesArea(?int $candidateAreaId, ?int $areaId): bool
    {
        return ! $areaId || $candidateAreaId === (int) $areaId;
    }

    private function sumActionRows(array $rows, callable $filter): int
    {
        return (int) collect($rows)
            ->filter($filter)
            ->sum('total');
    }

    private function latestActionRows(array $rows, callable $filter): string
    {
        return (string) collect($rows)
            ->filter($filter)
            ->max('latest_at');
    }

    private function reviewStats(?int $areaId): array
    {
        if (! $areaId) {
            return [];
        }

        $cacheKey = "area:{$areaId}";
        if (array_key_exists($cacheKey, $this->reviewStatsCache)) {
            return $this->reviewStatsCache[$cacheKey];
        }

        return $this->reviewStatsCache[$cacheKey] = Peminjaman::query()
            ->whereIn('status', [
                Peminjaman::STATUS_MENUNGGU_REVIEW,
                Peminjaman::STATUS_MENUNGGU_REVIEW_AREA_PEMINJAM,
            ])
            ->where(function (Builder $query) use ($areaId) {
                $query
                    ->where('area_id', $areaId)
                    ->orWhere('requester_area_id', $areaId);
            })
            ->selectRaw('status, kategori, is_inter_area, area_id, requester_area_id, COUNT(*) as total, MAX(updated_at) as latest_at')
            ->groupBy('status', 'kategori', 'is_inter_area', 'area_id', 'requester_area_id')
            ->get()
            ->map(fn ($row) => [
                'status' => (string) $row->status,
                'kategori' => $row->kategori,
                'is_inter_area' => $this->toBool($row->is_inter_area),
                'area_id' => $this->normalizeAreaId($row->area_id),
                'requester_area_id' => $this->normalizeAreaId($row->requester_area_id),
                'total' => (int) $row->total,
                'latest_at' => (string) ($row->latest_at ?? ''),
            ])
            ->all();
    }

    private function laporanStats(string $roleKey, ?int $areaId, bool $shouldFilterArea): array
    {
        $cacheKey = implode('|', [
            'laporan',
            $roleKey,
            $shouldFilterArea ? "area:{$areaId}" : 'all',
        ]);

        if (array_key_exists($cacheKey, $this->laporanStatsCache)) {
            return $this->laporanStatsCache[$cacheKey];
        }

        if ($shouldFilterArea && ! $areaId) {
            return $this->laporanStatsCache[$cacheKey] = [];
        }

        return $this->laporanStatsCache[$cacheKey] = LaporanAlat::query()
            ->where('status', 'Dilaporkan')
            ->when($shouldFilterArea, fn (Builder $query) => $query->where('area_id', $areaId))
            ->selectRaw('kategori, COUNT(*) as total, MAX(updated_at) as latest_at')
            ->groupBy('kategori')
            ->get()
            ->mapWithKeys(fn ($row) => [
                (string) $row->kategori => [
                    'total' => (int) $row->total,
                    'latest_at' => (string) ($row->latest_at ?? ''),
                ],
            ])
            ->all();
    }

    private function sidebarCounts(string $roleKey, ?int $areaId): array
    {
        $sidebar = $this->emptyPayload()['sidebar'];

        if (in_array($roleKey, [Role::KEY_SP_TOOL, Role::KEY_MGR_TOOL, Role::KEY_SUPER_ADMIN], true)) {
            $sidebar['review'] = $this->reviewCount($areaId);
        }

        if (in_array($roleKey, [Role::KEY_GUEST, Role::KEY_SP_TOOL, Role::KEY_PIC_TOOL, Role::KEY_MGR_TOOL, Role::KEY_ADMIN, Role::KEY_SUPER_ADMIN], true)) {
            ['kerusakan' => $sidebar['kerusakan'], 'kehilangan' => $sidebar['kehilangan']] = $this->laporanCounts($roleKey, $areaId);
        }

        if (in_array($roleKey, [Role::KEY_PIC_TOOL, Role::KEY_ADMIN, Role::KEY_SUPER_ADMIN], true)) {
            $sidebar['pengiriman_intra_area'] = $this->shippingSidebarCount($areaId, Peminjaman::KATEGORI_INTRA_AREA);
            $sidebar['pengiriman_antar_area'] = $this->shippingSidebarCount(
                $areaId,
                Peminjaman::KATEGORI_ANTAR_AREA,
                $roleKey !== Role::KEY_SUPER_ADMIN
            );
        }

        if (in_array($roleKey, [Role::KEY_GUEST, Role::KEY_MGR_TOOL, Role::KEY_SUPER_ADMIN], true)) {
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
                Peminjaman::STATUS_DISETUJUI,
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
            Peminjaman::STATUS_DISETUJUI,
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
        if (! $areaId) {
            return 0;
        }

        return $this->sumActionRows(
            $this->actionStats($areaId),
            fn (array $row) => $this->matchesStatus($row, $statuses)
                && (
                    ($this->matchesKategori($row, Peminjaman::KATEGORI_INTRA_AREA) && $this->matchesArea($row['area_id'], $areaId))
                    || ($this->matchesKategori($row, Peminjaman::KATEGORI_ANTAR_AREA) && $this->matchesArea($row['requester_area_id'], $areaId))
                )
        );
    }

    private function latestMutasiAction(?int $areaId, array $statuses): string
    {
        if (! $areaId) {
            return '';
        }

        return $this->latestActionRows(
            $this->actionStats($areaId),
            fn (array $row) => $this->matchesStatus($row, $statuses)
                && (
                    ($this->matchesKategori($row, Peminjaman::KATEGORI_INTRA_AREA) && $this->matchesArea($row['area_id'], $areaId))
                    || ($this->matchesKategori($row, Peminjaman::KATEGORI_ANTAR_AREA) && $this->matchesArea($row['requester_area_id'], $areaId))
                )
        );
    }

    private function reviewCount(?int $areaId): int
    {
        if (! $areaId) {
            return 0;
        }

        return $this->sumActionRows(
            $this->reviewStats($areaId),
            fn (array $row) => (
                $row['status'] === Peminjaman::STATUS_MENUNGGU_REVIEW
                && $this->matchesArea($row['area_id'], $areaId)
            ) || (
                $row['status'] === Peminjaman::STATUS_MENUNGGU_REVIEW_AREA_PEMINJAM
                && $this->matchesKategori($row, Peminjaman::KATEGORI_ANTAR_AREA)
                && $this->matchesArea($row['requester_area_id'], $areaId)
            )
        );
    }

    private function laporanCounts(string $roleKey, ?int $areaId): array
    {
        $stats = $this->laporanStats(
            $roleKey,
            $areaId,
            in_array($roleKey, [Role::KEY_GUEST, Role::KEY_SP_TOOL, Role::KEY_PIC_TOOL, Role::KEY_MGR_TOOL], true)
                || ! empty($areaId)
        );

        return [
            'kerusakan' => (int) ($stats[LaporanAlat::CATEGORY_KERUSAKAN]['total'] ?? 0),
            'kehilangan' => (int) ($stats[LaporanAlat::CATEGORY_KEHILANGAN]['total'] ?? 0),
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
            in_array($roleKey, [Role::KEY_PIC_TOOL, Role::KEY_ADMIN], true) => [
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
        $shouldFilterArea = in_array($roleKey, [Role::KEY_SP_TOOL, Role::KEY_PIC_TOOL, Role::KEY_MGR_TOOL], true)
            || ! empty($areaId);

        $stats = $this->laporanStats($roleKey, $areaId, $shouldFilterArea);

        return (string) ($stats[$kategori]['latest_at'] ?? '');
    }

    private function picToolMailboxItems(?int $areaId, bool $includeRequesterActions = true): array
    {
        $items = [
            [
                'key' => 'pengiriman-intra-siap',
                'title' => 'Peminjaman - Intra Area - Disetujui',
                'description' => 'Perlu diproses pengiriman',
                'count' => $this->countSourceAction($areaId, Peminjaman::KATEGORI_INTRA_AREA, [Peminjaman::STATUS_DISETUJUI]),
                'href' => '/pengiriman-alat?tab=disetujui',
                'sort_at' => $this->latestSourceAction($areaId, Peminjaman::KATEGORI_INTRA_AREA, [Peminjaman::STATUS_DISETUJUI]),
            ],
            [
                'key' => 'pengiriman-antar-siap',
                'title' => 'Peminjaman - Antar Area - Disetujui',
                'description' => 'Perlu diproses pengiriman',
                'count' => $this->countSourceAction($areaId, Peminjaman::KATEGORI_ANTAR_AREA, [Peminjaman::STATUS_DISETUJUI]),
                'href' => '/pengiriman-antar-area?tab=disetujui',
                'sort_at' => $this->latestSourceAction($areaId, Peminjaman::KATEGORI_ANTAR_AREA, [Peminjaman::STATUS_DISETUJUI]),
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

        $reviewStats = $this->reviewStats($areaId);
        $intraCount = $this->sumActionRows(
            $reviewStats,
            fn (array $row) => $row['status'] === Peminjaman::STATUS_MENUNGGU_REVIEW
                && $this->matchesKategori($row, Peminjaman::KATEGORI_INTRA_AREA)
                && $this->matchesArea($row['area_id'], $areaId)
        );
        $antarCount = $this->sumActionRows(
            $reviewStats,
            fn (array $row) => $this->matchesKategori($row, Peminjaman::KATEGORI_ANTAR_AREA)
                && (
                    ($row['status'] === Peminjaman::STATUS_MENUNGGU_REVIEW_AREA_PEMINJAM && $this->matchesArea($row['requester_area_id'], $areaId))
                    || ($row['status'] === Peminjaman::STATUS_MENUNGGU_REVIEW && $this->matchesArea($row['area_id'], $areaId))
                )
        );

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

        return $this->latestActionRows(
            $this->reviewStats($areaId),
            fn (array $row) => $this->matchesKategori($row, Peminjaman::KATEGORI_ANTAR_AREA)
                && (
                    ($row['status'] === Peminjaman::STATUS_MENUNGGU_REVIEW_AREA_PEMINJAM && $this->matchesArea($row['requester_area_id'], $areaId))
                    || ($row['status'] === Peminjaman::STATUS_MENUNGGU_REVIEW && $this->matchesArea($row['area_id'], $areaId))
                )
        );
    }

    private function userMailboxItems(int $userId): array
    {
        return [
            [
                'key' => 'user-penerimaan',
                'title' => 'Peminjaman - Intra Area - Dikirim',
                'description' => 'Perlu diterima',
                'count' => $this->userActionCount($userId, [Peminjaman::STATUS_DIKIRIM]),
                'href' => '/mutasi-alat?tab=dikirim',
                'sort_at' => $this->latestUserAction($userId, [Peminjaman::STATUS_DIKIRIM]),
            ],
            [
                'key' => 'user-pengembalian',
                'title' => 'Peminjaman - Intra Area - Diterima',
                'description' => 'Perlu dikembalikan',
                'count' => $this->userActionCount($userId, [Peminjaman::STATUS_DITERIMA, Peminjaman::STATUS_DIKEMBALIKAN_PARTIALS]),
                'href' => '/mutasi-alat?tab=diterima',
                'sort_at' => $this->latestUserAction($userId, [Peminjaman::STATUS_DITERIMA, Peminjaman::STATUS_DIKEMBALIKAN_PARTIALS]),
            ],
        ];
    }

    private function userActionCount(int $userId, array $statuses): int
    {
        return $this->sumActionRows(
            $this->userActionStats($userId),
            fn (array $row) => $this->matchesStatus($row, $statuses)
                && $this->matchesKategori($row, Peminjaman::KATEGORI_INTRA_AREA)
        );
    }

    private function latestUserAction(int $userId, array $statuses): string
    {
        return $this->latestActionRows(
            $this->userActionStats($userId),
            fn (array $row) => $this->matchesStatus($row, $statuses)
                && $this->matchesKategori($row, Peminjaman::KATEGORI_INTRA_AREA)
        );
    }

    private function userActionStats(int $userId): array
    {
        if (array_key_exists($userId, $this->userActionStatsCache)) {
            return $this->userActionStatsCache[$userId];
        }

        return $this->userActionStatsCache[$userId] = $this->peminjamanActionQuery([
            Peminjaman::STATUS_DIKIRIM,
            Peminjaman::STATUS_DITERIMA,
            Peminjaman::STATUS_DIKEMBALIKAN_PARTIALS,
        ])
            ->where('user_id', $userId)
            ->selectRaw('status, kategori, is_inter_area, COUNT(*) as total, MAX(updated_at) as latest_at')
            ->groupBy('status', 'kategori', 'is_inter_area')
            ->get()
            ->map(fn ($row) => [
                'status' => (string) $row->status,
                'kategori' => $row->kategori,
                'is_inter_area' => $this->toBool($row->is_inter_area),
                'total' => (int) $row->total,
                'latest_at' => (string) ($row->latest_at ?? ''),
            ])
            ->all();
    }

    private function normalizeAreaId($areaId): ?int
    {
        return $areaId === null || $areaId === '' ? null : (int) $areaId;
    }

    private function toBool($value): bool
    {
        return in_array($value, [true, 1, '1', 'true', 'TRUE', 'Y', 'y'], true);
    }
}
