<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Middleware\EnforceSessionTimeout;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SessionController extends Controller
{
    /**
     * Dipanggil browser saat pengguna benar-benar beraktivitas (mengetik di
     * form panjang, menggeser halaman) dan saat menekan "Tetap masuk" di modal
     * peringatan. Sengaja tidak menyentuh database supaya ringan: pembaruan
     * penanda aktivitas sudah dilakukan oleh EnforceSessionTimeout.
     */
    public function heartbeat(Request $request): JsonResponse
    {
        return response()->json(array_merge(
            EnforceSessionTimeout::remainingFor($request),
            ['warning_lead_time' => max(0, (int) config('session.warning_lead_time', 0))]
        ));
    }
}
