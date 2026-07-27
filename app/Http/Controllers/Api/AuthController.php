<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\LdapLoginException;
use App\Http\Controllers\Controller;
use App\Services\LdapLoginService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function login(Request $request, LdapLoginService $ldapLoginService): JsonResponse
    {
        $validated = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        try {
            $user = $ldapLoginService->attempt($validated['username'], $validated['password']);
        } catch (LdapLoginException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], $exception->status());
        }

        if ($user->exists) {
            $user->tokens()->delete();
        }

        Auth::guard('web')->login($user);

        if ($request->hasSession()) {
            $request->session()->regenerate();
        }

        $user->loadMissing(['area', 'role']);

        return response()->json([
            'user' => $user,
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();
        $user?->tokens()->delete();

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json([
            'message' => 'Logout berhasil.',
        ]);
    }
}
