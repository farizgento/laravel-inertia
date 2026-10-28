<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to your application's "home" route.
     *
     * Typically, users are redirected here after authentication.
     *
     * @var string
     */
    public const HOME = '/dashboard';

    /**
     * Define your route model bindings, pattern filters, and other route configuration.
     */
    public function boot(): void
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        // Login diadu ke LDAP, jadi percobaan brute force tidak hanya berisiko
        // secara keamanan tetapi juga bisa mengunci akun domain pengguna.
        // Batas per username dibuat ketat; batas per IP dibuat longgar karena
        // banyak pengguna di jaringan kantor berbagi satu IP publik (NAT) dan
        // bisa login hampir bersamaan di awal shift.
        RateLimiter::for('login', function (Request $request) {
            $username = strtolower(trim((string) $request->input('username')));

            return [
                Limit::perMinute(5)->by('login:'.$username.'|'.$request->ip()),
                Limit::perMinute(60)->by('login-ip:'.$request->ip()),
            ];
        });

        $this->routes(function () {
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/api.php'));

            Route::middleware('web')
                ->group(base_path('routes/web.php'));
        });
    }
}
