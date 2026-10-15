<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\LdapLoginException;
use App\Http\Controllers\Controller;
use App\Services\ActivityLogger;
use App\Services\LdapLoginService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Throwable;

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

        // Dicatat supaya keaktifan tiap area bisa diukur di halaman Analytics.
        // Kegagalan pencatatan tidak boleh membuat pengguna gagal masuk.
        try {
            ActivityLogger::log('login', $user, [
                'actor' => $user,
                'subject_type' => 'Auth',
                'subject_label' => $user->name,
                'area_id' => $user->area_id,
                'description' => $user->name.' masuk ke aplikasi',
            ]);
        } catch (Throwable $exception) {
            report($exception);
        }

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
