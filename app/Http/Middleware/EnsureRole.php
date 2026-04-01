<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();
        $message = 'Anda tidak memiliki akses.';

        if (! $user) {
            return redirect()->route('login');
        }

        $user->loadMissing('role');

        if (! $user->role || ! in_array($user->role->key, $roles, true)) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json(['message' => $message], 403);
            }

            $previousUrl = url()->previous();
            $fallbackUrl = route('dashboard');
            $targetUrl = $previousUrl && $previousUrl !== $request->fullUrl()
                ? $previousUrl
                : $fallbackUrl;

            return redirect()->to($targetUrl)->with('error', $message);
        }

        return $next($request);
    }
}
