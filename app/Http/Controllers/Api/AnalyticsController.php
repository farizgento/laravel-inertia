<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Area;
use App\Models\Role;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;

class AnalyticsController extends Controller
{
    /**
     * Area yang boleh membuka Analytics selain super admin.
     */
    public const KODE_AREA_PENGELOLA = 'TRLA';

    /**
     * Analytics memandang seluruh area sekaligus, jadi hanya super admin dan
     * pengelola pusat (SP Tool, Mgr Tool, dan admin area TRLA) yang boleh membuka.
     */
    public static function bolehMengakses(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        $user->loadMissing(['role', 'area']);
        $roleKey = strtolower((string) ($user->role?->key ?? ''));

        if ($roleKey === Role::KEY_SUPER_ADMIN) {
            return true;
        }

        if (! in_array($roleKey, [Role::KEY_SP_TOOL, Role::KEY_MGR_TOOL, Role::KEY_ADMIN], true)) {
            return false;
        }

        return strtoupper((string) ($user->area?->kode ?? '')) === self::KODE_AREA_PENGELOLA;
    }

    public function __invoke(Request $request)
    {
        abort_unless(self::bolehMengakses($request->user()), 403, 'Anda tidak memiliki akses ke Analytics.');

        $validated = $request->validate([
            'hari' => ['nullable', 'integer', 'min:1', 'max:365'],
        ]);

        $hari = (int) ($validated['hari'] ?? 30);
        $mulai = CarbonImmutable::now()->startOfDay()->subDays($hari - 1);

        $areas = Area::query()->orderBy('name')->get(['id', 'name', 'kode']);
        $penggunaPerArea = User::query()
            ->selectRaw('area_id, COUNT(*) as total')
            ->groupBy('area_id')
            ->pluck('total', 'area_id');

        $log = ActivityLog::query()->where('created_at', '>=', $mulai->toDateTimeString());

        $aktivitasPerArea = (clone $log)
            ->selectRaw('actor_area_id, COUNT(*) as total')
            ->groupBy('actor_area_id')
            ->pluck('total', 'actor_area_id');

        $loginPerArea = (clone $log)
            ->where('action', 'login')
            ->selectRaw('actor_area_id, COUNT(*) as total')
            ->groupBy('actor_area_id')
            ->pluck('total', 'actor_area_id');

        $penggunaAktifPerArea = (clone $log)
            ->whereNotNull('user_id')
            ->selectRaw('actor_area_id, COUNT(DISTINCT user_id) as total')
            ->groupBy('actor_area_id')
            ->pluck('total', 'actor_area_id');

        $terakhirAktifPerArea = (clone $log)
            ->selectRaw('actor_area_id, MAX(created_at) as terakhir')
            ->groupBy('actor_area_id')
            ->pluck('terakhir', 'actor_area_id');

        // Warna dipakai berulang bila jumlah area melebihi palet.
        $palet = ['#6366f1', '#0ea5e9', '#10b981', '#f59e0b', '#ec4899', '#8b5cf6', '#14b8a6', '#f43f5e', '#84cc16', '#06b6d4'];

        $baris = $areas->values()->map(function (Area $area, int $index) use (
            $palet,
            $penggunaPerArea,
            $aktivitasPerArea,
            $loginPerArea,
            $penggunaAktifPerArea,
            $terakhirAktifPerArea
        ) {
            $pengguna = (int) ($penggunaPerArea[$area->id] ?? 0);
            $aktif = (int) ($penggunaAktifPerArea[$area->id] ?? 0);
            $terakhir = $terakhirAktifPerArea[$area->id] ?? null;

            return [
                'key' => 'area-'.$area->id,
                'area_id' => $area->id,
                'label' => $area->name,
                'kode' => $area->kode,
                'warna' => $palet[$index % count($palet)],
                'aktivitas' => (int) ($aktivitasPerArea[$area->id] ?? 0),
                'login' => (int) ($loginPerArea[$area->id] ?? 0),
                'pengguna' => $pengguna,
                'pengguna_aktif' => $aktif,
                'persen_pengguna_aktif' => $pengguna > 0 ? (int) round(($aktif / $pengguna) * 100) : 0,
                'terakhir_aktif' => $terakhir ? CarbonImmutable::parse($terakhir)->format('d M Y H:i') : null,
            ];
        });

        $rincianAksi = (clone $log)
            ->selectRaw('action, COUNT(*) as total')
            ->groupBy('action')
            ->orderByDesc('total')
            ->pluck('total', 'action')
            ->map(fn ($nilai) => (int) $nilai);

        return [
            'periode' => [
                'hari' => $hari,
                'mulai' => $mulai->translatedFormat('d M Y'),
                'selesai' => CarbonImmutable::now()->translatedFormat('d M Y'),
            ],
            'ringkasan' => [
                'total_aktivitas' => (int) $aktivitasPerArea->sum(),
                'total_login' => (int) $loginPerArea->sum(),
                'pengguna_aktif' => (int) $penggunaAktifPerArea->sum(),
                'total_pengguna' => (int) $penggunaPerArea->sum(),
                'area_aktif' => $baris->where('aktivitas', '>', 0)->count(),
                'total_area' => $baris->count(),
            ],
            'area' => $baris->sortByDesc('aktivitas')->values(),
            'rincian_aksi' => $rincianAksi,
        ];
    }
}
