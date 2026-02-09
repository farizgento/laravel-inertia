<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
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
        $authUser = null;
        $user = $request->user();

        if ($user) {
            $user->loadMissing(['area', 'role']);
            $authUser = $this->mapUser($user);
        } else {
            $bearerToken = $request->bearerToken();
            if ($bearerToken) {
                $cacheKey = 'sanctum_user:' . hash('sha256', $bearerToken);
                $authUser = Cache::get($cacheKey);

                if (! $authUser) {
                    $tokenUser = auth('sanctum')->user();
                    if ($tokenUser) {
                        $tokenUser->loadMissing(['area', 'role']);
                        $authUser = $this->mapUser($tokenUser);
                        Cache::put($cacheKey, $authUser, now()->addMinutes(5));
                    }
                }
            }
        }

        return array_merge(parent::share($request), [
            'appName' => config('app.name'),
            'auth' => [
                'user' => $authUser,
            ],
        ]);
    }

    private function mapUser($user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'area' => $user->area
                ? [
                    'id' => $user->area->id,
                    'name' => $user->area->name,
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
