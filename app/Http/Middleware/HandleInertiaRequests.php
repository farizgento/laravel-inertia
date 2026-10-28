<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        $user = $request->user();

        if ($user) {
            $user->loadMissing(['area', 'role']);
            $authUser = $this->mapUser($user);
        } else {
            $authUser = null;
        }

        return array_merge(parent::share($request), [
            'appName' => config('app.name'),
            'auth' => [
                'user' => $authUser,
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
            // Dibagikan agar timer di browser memakai angka yang sama dengan
            // yang ditegakkan server, tanpa perlu request tambahan. Semua
            // dalam detik; -1 berarti pemeriksaannya dimatikan.
            'sessionTimeout' => [
                'idle' => EnforceSessionTimeout::idleLimit() ?: -1,
                'absolute' => EnforceSessionTimeout::absoluteLimit() ?: -1,
                'warningLeadTime' => max(0, (int) config('session.warning_lead_time', 0)),
            ],
        ]);
    }

    private function mapUser($user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'username' => $user->username,
            'email' => $user->email,
            'area' => $user->area
                ? [
                    'id' => $user->area->id,
                    'name' => $user->area->name,
                    'kode' => $user->area->kode,
                ]
                : null,
            'role' => $user->role
                ? [
                    'id' => $user->role->id,
                    'name' => $user->role->name,
                    'key' => $user->role->key,
                ]
                : null,
        ];
    }
}
