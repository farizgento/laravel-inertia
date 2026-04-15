<?php

use App\Http\Controllers\Api\AlatController;
use App\Http\Controllers\Api\ActivityLogController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AreaController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\LaporanAlatController;
use App\Http\Controllers\Api\UserManagementController;
use App\Http\Controllers\api\MutasiAlatController;
use App\Http\Controllers\Api\PeminjamanController;
use App\Http\Controllers\Api\PengirimanController;
use App\Http\Controllers\Api\ReviewPeminjamanController;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);

Route::get('/areas', [AreaController::class, 'index']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user()->load(['area', 'role']);
    });

    Route::get('/dashboard', DashboardController::class);

    Route::post('/auth/logout', [AuthController::class, 'logout']);

    Route::middleware('role:' . implode(',', [
        Role::KEY_ADMIN,
        Role::KEY_SUPER_ADMIN,
    ]))->group(function () {
        Route::get('/users', [UserManagementController::class, 'index']);
        Route::post('/users', [UserManagementController::class, 'store']);
        Route::put('/users/{user}', [UserManagementController::class, 'update']);
        Route::delete('/users/{user}', [UserManagementController::class, 'destroy']);
    });

    Route::middleware('role:' . implode(',', [
        Role::KEY_SP_TOOL,
        Role::KEY_MGR_TOOL,
        Role::KEY_ADMIN,
        Role::KEY_SUPER_ADMIN,
    ]))->group(function () {
        Route::get('/activity-logs', [ActivityLogController::class, 'index']);
        Route::get('/activity-logs/export', [ActivityLogController::class, 'export']);
    });

    Route::middleware('role:' . Role::KEY_SUPER_ADMIN)->group(function () {
        Route::get('/admin/areas', [AreaController::class, 'managementIndex']);
        Route::post('/admin/areas', [AreaController::class, 'store']);
        Route::put('/admin/areas/{area}', [AreaController::class, 'update']);
        Route::delete('/admin/areas/{area}', [AreaController::class, 'destroy']);
    });

    Route::middleware('role:' . implode(',', [
        Role::KEY_USER,
        Role::KEY_SP_TOOL,
        Role::KEY_PIC_TOOLS,
        Role::KEY_MGR_TOOL,
        Role::KEY_ADMIN,
        Role::KEY_SUPER_ADMIN,
    ]))->group(function () {
        Route::get('/peminjaman', [PeminjamanController::class, 'index']);
    });

    Route::middleware('role:' . implode(',', [
        Role::KEY_USER,
        Role::KEY_SUPER_ADMIN,
    ]))->group(function () {
        Route::post('/peminjaman', [PeminjamanController::class, 'store']);
        Route::post('/pengiriman/{peminjaman}/terima', [PengirimanController::class, 'terima']);
        Route::post('/pengiriman/{peminjaman}/kembalikan', [PengirimanController::class, 'kembalikan']);
    });

    Route::middleware('role:' . implode(',', [
        Role::KEY_SP_TOOL,
        Role::KEY_MGR_TOOL,
        Role::KEY_SUPER_ADMIN,
    ]))->group(function () {
        Route::get('/review-peminjaman/pending-count', [ReviewPeminjamanController::class, 'pendingCount']);
        Route::get('/review-peminjaman', [ReviewPeminjamanController::class, 'index']);
        Route::post('/review-peminjaman/{peminjaman}', [ReviewPeminjamanController::class, 'review']);
    });

    Route::get('/alats', [AlatController::class, 'index'])
        ->middleware('role:' . implode(',', [
            Role::KEY_USER,
            Role::KEY_SP_TOOL,
            Role::KEY_PIC_TOOLS,
            Role::KEY_MGR_TOOL,
            Role::KEY_ADMIN,
            Role::KEY_SUPER_ADMIN,
        ]));
    Route::get('/alats/export', [AlatController::class, 'export'])
        ->middleware('role:' . implode(',', [
            Role::KEY_USER,
            Role::KEY_SP_TOOL,
            Role::KEY_PIC_TOOLS,
            Role::KEY_MGR_TOOL,
            Role::KEY_ADMIN,
            Role::KEY_SUPER_ADMIN,
        ]));

    Route::middleware('role:' . implode(',', [
        Role::KEY_PIC_TOOLS,
        Role::KEY_ADMIN,
        Role::KEY_SUPER_ADMIN,
    ]))->group(function () {
        Route::post('/alats', [AlatController::class, 'store']);
        Route::post('/alats/import', [AlatController::class, 'import']);
        Route::put('/alats/{alat}', [AlatController::class, 'update']);
        Route::delete('/alats/{alat}', [AlatController::class, 'destroy']);
    });

    Route::middleware('role:' . implode(',', [
        Role::KEY_ADMIN,
        Role::KEY_SUPER_ADMIN,
        Role::KEY_PIC_TOOLS,
    ]))->group(function () {
        Route::get('/pengiriman', [PengirimanController::class, 'index']);
        Route::post('/pengiriman/{peminjaman}/siapkan', [PengirimanController::class, 'siapkan']);
        Route::post('/pengiriman/{peminjaman}/kirim', [PengirimanController::class, 'kirim']);
    });

    Route::get('/riwayat-pengiriman', [MutasiAlatController::class, 'index'])
        ->middleware('role:' . implode(',', [
            Role::KEY_USER,
            Role::KEY_SP_TOOL,
            Role::KEY_PIC_TOOLS,
            Role::KEY_MGR_TOOL,
            Role::KEY_ADMIN,
            Role::KEY_SUPER_ADMIN,
        ]));

    Route::get('/laporan-kerusakan', [LaporanAlatController::class, 'indexKerusakan'])
        ->middleware('role:' . implode(',', [
            Role::KEY_SP_TOOL,
            Role::KEY_PIC_TOOLS,
            Role::KEY_MGR_TOOL,
            Role::KEY_ADMIN,
            Role::KEY_SUPER_ADMIN,
        ]));
    Route::get('/laporan-kerusakan/export', [LaporanAlatController::class, 'exportKerusakan'])
        ->middleware('role:' . implode(',', [
            Role::KEY_SP_TOOL,
            Role::KEY_PIC_TOOLS,
            Role::KEY_MGR_TOOL,
            Role::KEY_ADMIN,
            Role::KEY_SUPER_ADMIN,
        ]));
    Route::get('/laporan-pending-counts', [LaporanAlatController::class, 'pendingCounts'])
        ->middleware('role:' . implode(',', [
            Role::KEY_SP_TOOL,
            Role::KEY_PIC_TOOLS,
            Role::KEY_MGR_TOOL,
            Role::KEY_ADMIN,
            Role::KEY_SUPER_ADMIN,
        ]));
    Route::post('/laporan-kerusakan', [LaporanAlatController::class, 'storeKerusakan'])
        ->middleware('role:' . implode(',', [
            Role::KEY_SP_TOOL,
            Role::KEY_PIC_TOOLS,
            Role::KEY_MGR_TOOL,
            Role::KEY_ADMIN,
            Role::KEY_SUPER_ADMIN,
        ]));
    Route::put('/laporan-kerusakan/{laporan}', [LaporanAlatController::class, 'updateKerusakanStatus'])
        ->middleware('role:' . implode(',', [
            Role::KEY_SP_TOOL,
            Role::KEY_PIC_TOOLS,
            Role::KEY_MGR_TOOL,
            Role::KEY_ADMIN,
            Role::KEY_SUPER_ADMIN,
        ]));

    Route::get('/laporan-kehilangan', [LaporanAlatController::class, 'indexKehilangan'])
        ->middleware('role:' . implode(',', [
            Role::KEY_SP_TOOL,
            Role::KEY_PIC_TOOLS,
            Role::KEY_MGR_TOOL,
            Role::KEY_ADMIN,
            Role::KEY_SUPER_ADMIN,
        ]));
    Route::get('/laporan-kehilangan/export', [LaporanAlatController::class, 'exportKehilangan'])
        ->middleware('role:' . implode(',', [
            Role::KEY_SP_TOOL,
            Role::KEY_PIC_TOOLS,
            Role::KEY_MGR_TOOL,
            Role::KEY_ADMIN,
            Role::KEY_SUPER_ADMIN,
        ]));
    Route::post('/laporan-kehilangan', [LaporanAlatController::class, 'storeKehilangan'])
        ->middleware('role:' . implode(',', [
            Role::KEY_SP_TOOL,
            Role::KEY_PIC_TOOLS,
            Role::KEY_MGR_TOOL,
            Role::KEY_ADMIN,
            Role::KEY_SUPER_ADMIN,
        ]));
    Route::put('/laporan-kehilangan/{laporan}', [LaporanAlatController::class, 'updateKehilanganStatus'])
        ->middleware('role:' . implode(',', [
            Role::KEY_SP_TOOL,
            Role::KEY_PIC_TOOLS,
            Role::KEY_MGR_TOOL,
            Role::KEY_ADMIN,
            Role::KEY_SUPER_ADMIN,
        ]));
});
