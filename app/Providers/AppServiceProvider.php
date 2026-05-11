<?php

namespace App\Providers;

use App\Models\Alat;
use App\Models\Area;
use App\Models\LaporanAlat;
use App\Models\Peminjaman;
use App\Models\PeminjamanItem;
use App\Models\SuratJalan;
use App\Models\User;
use App\Observers\ModelActivityObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $observer = ModelActivityObserver::class;

        User::observe($observer);
        Area::observe($observer);
        Alat::observe($observer);
        Peminjaman::observe($observer);
        PeminjamanItem::observe($observer);
        SuratJalan::observe($observer);
        LaporanAlat::observe($observer);
    }
}
