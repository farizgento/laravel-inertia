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
use Illuminate\Database\Events\ConnectionEstablished;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Throwable;

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
        $this->disableOracleBitmapPlans();

        $observer = ModelActivityObserver::class;

        User::observe($observer);
        Area::observe($observer);
        Alat::observe($observer);
        Peminjaman::observe($observer);
        PeminjamanItem::observe($observer);
        SuratJalan::observe($observer);
        LaporanAlat::observe($observer);
    }

    /**
     * Oracle 21c XE mengembalikan baris yang SALAH (kosong) untuk rencana
     * "BITMAP CONVERSION FROM ROWIDS". Rencana itu dipilih saat satu query
     * menyaring tiga kolom berindeks sekaligus, misalnya pada Log Alat yang
     * menyaring subject_type + area_id + action secara bersamaan: hasilnya 0
     * baris padahal datanya ada. Query yang sama memberi hasil benar begitu
     * rencana bitmap dimatikan.
     *
     * Dimatikan di tingkat sesi supaya seluruh query aplikasi ikut terlindungi,
     * bukan hanya query yang kebetulan sudah ketahuan bermasalah.
     */
    private function disableOracleBitmapPlans(): void
    {
        Event::listen(ConnectionEstablished::class, function (ConnectionEstablished $event): void {
            if ($event->connection->getDriverName() !== 'oracle') {
                return;
            }

            try {
                $event->connection->statement('ALTER SESSION SET "_b_tree_bitmap_plans" = FALSE');
            } catch (Throwable) {
                // Parameter ini tidak wajib ada di semua edisi Oracle. Bila
                // ditolak, biarkan koneksi tetap berjalan apa adanya.
            }
        });
    }
}
