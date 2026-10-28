<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Menegakkan dua batas waktu sesi di sisi server:
 *
 * - idle timeout, dihitung dari aktivitas nyata pengguna. Request polling yang
 *   membawa header X-Silent-Poll sengaja tidak memperpanjang sesi, karena kalau
 *   ikut dihitung idle timeout tidak akan pernah tercapai selama tab dibiarkan
 *   terbuka.
 * - absolute timeout, dihitung sejak login sehingga sesi tidak bisa hidup
 *   selamanya meski pengguna terus beraktivitas.
 *
 * Timer di browser hanya untuk kenyamanan (modal peringatan). Penegakan yang
 * dipercaya ada di sini supaya tidak bisa dilewati dengan mematikan JavaScript
 * atau memanggil API langsung.
 */
class EnforceSessionTimeout
{
    public const SESSION_STARTED_AT = 'session_started_at';

    public const LAST_ACTIVITY_AT = 'session_last_activity_at';

    public const REASON_IDLE = 'session_idle_expired';

    public const REASON_ABSOLUTE = 'session_absolute_expired';

    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->hasSession() || ! Auth::guard('web')->check()) {
            return $next($request);
        }

        $session = $request->session();
        $now = time();

        $startedAt = (int) $session->get(self::SESSION_STARTED_AT, 0);
        $lastActivityAt = (int) $session->get(self::LAST_ACTIVITY_AT, 0);

        // Sesi yang sudah berjalan sebelum fitur ini aktif belum punya penanda,
        // jadi dianggap baru dimulai sekarang agar tidak langsung ter-logout.
        if ($startedAt <= 0) {
            $startedAt = $now;
            $session->put(self::SESSION_STARTED_AT, $startedAt);
        }

        if ($lastActivityAt <= 0) {
            $lastActivityAt = $now;
            $session->put(self::LAST_ACTIVITY_AT, $lastActivityAt);
        }

        $idleLimit = self::idleLimit();
        $absoluteLimit = self::absoluteLimit();

        if ($absoluteLimit > 0 && ($now - $startedAt) >= $absoluteLimit) {
            return $this->expire($request, self::REASON_ABSOLUTE);
        }

        if ($idleLimit > 0 && ($now - $lastActivityAt) >= $idleLimit) {
            return $this->expire($request, self::REASON_IDLE);
        }

        if (! $this->isSilentPoll($request)) {
            $lastActivityAt = $now;
            $session->put(self::LAST_ACTIVITY_AT, $lastActivityAt);
        }

        $response = $next($request);

        // Sisa waktu dikirim lewat header supaya browser bisa menyelaraskan
        // timer-nya tanpa request tambahan.
        $response->headers->set(
            'X-Session-Idle-Expires-In',
            (string) self::remaining($idleLimit, $lastActivityAt, $now)
        );
        $response->headers->set(
            'X-Session-Absolute-Expires-In',
            (string) self::remaining($absoluteLimit, $startedAt, $now)
        );

        return $response;
    }

    /**
     * Dipakai saat login supaya kedua penanda dimulai dari nol.
     */
    public static function markSessionStart(Request $request): void
    {
        if (! $request->hasSession()) {
            return;
        }

        $now = time();
        $request->session()->put(self::SESSION_STARTED_AT, $now);
        $request->session()->put(self::LAST_ACTIVITY_AT, $now);
    }

    /**
     * Sisa waktu sesi dalam detik. Nilai -1 berarti pemeriksaan dimatikan.
     *
     * @return array{idle_expires_in: int, absolute_expires_in: int}
     */
    public static function remainingFor(Request $request): array
    {
        $now = time();
        $session = $request->hasSession() ? $request->session() : null;

        return [
            'idle_expires_in' => self::remaining(
                self::idleLimit(),
                (int) ($session?->get(self::LAST_ACTIVITY_AT, $now) ?? $now),
                $now
            ),
            'absolute_expires_in' => self::remaining(
                self::absoluteLimit(),
                (int) ($session?->get(self::SESSION_STARTED_AT, $now) ?? $now),
                $now
            ),
        ];
    }

    public static function idleLimit(): int
    {
        return max(0, (int) config('session.idle_timeout', 0)) * 60;
    }

    public static function absoluteLimit(): int
    {
        return max(0, (int) config('session.absolute_timeout', 0)) * 60;
    }

    private static function remaining(int $limit, int $reference, int $now): int
    {
        if ($limit <= 0) {
            return -1;
        }

        return max(0, $limit - ($now - $reference));
    }

    private function isSilentPoll(Request $request): bool
    {
        return filter_var($request->header('X-Silent-Poll'), FILTER_VALIDATE_BOOLEAN);
    }

    private function expire(Request $request, string $reason): Response
    {
        Auth::guard('web')->logout();

        if ($request->hasSession()) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        $message = $reason === self::REASON_ABSOLUTE
            ? 'Sesi Anda sudah mencapai batas waktu maksimal. Silakan masuk kembali.'
            : 'Sesi Anda berakhir karena tidak ada aktivitas. Silakan masuk kembali.';

        $loginUrl = '/login?expired='.($reason === self::REASON_ABSOLUTE ? 'absolute' : 'idle');

        // Kunjungan Inertia butuh 409 + X-Inertia-Location, bukan redirect biasa,
        // supaya browser benar-benar pindah halaman.
        if ($request->header('X-Inertia')) {
            return response('', 409)->header('X-Inertia-Location', $loginUrl);
        }

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'message' => $message,
                'reason' => $reason,
            ], 401);
        }

        return redirect($loginUrl);
    }
}
