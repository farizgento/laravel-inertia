<?php

use App\Http\Controllers\Api\AlatController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AreaController;
use App\Http\Controllers\Api\PeminjamanController;
use App\Http\Controllers\Api\ReviewPeminjamanController;
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

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user()->load(['area', 'role']);
});

Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);
Route::middleware('auth:sanctum')->post('/auth/logout', [AuthController::class, 'logout']);

Route::get('/areas', [AreaController::class, 'index']);
Route::middleware('auth:sanctum')->get('/alats', [AlatController::class, 'index']);
Route::middleware('auth:sanctum')->post('/alats', [AlatController::class, 'store']);
Route::middleware('auth:sanctum')->put('/alats/{alat}', [AlatController::class, 'update']);
Route::middleware('auth:sanctum')->delete('/alats/{alat}', [AlatController::class, 'destroy']);
Route::middleware('auth:sanctum')->get('/peminjaman', [PeminjamanController::class, 'index']);
Route::middleware('auth:sanctum')->get('/review-peminjaman', [ReviewPeminjamanController::class, 'index']);
Route::middleware('auth:sanctum')->post('/review-peminjaman/{peminjaman}', [ReviewPeminjamanController::class, 'review']);
Route::middleware('auth:sanctum')->post('/peminjaman', [PeminjamanController::class, 'store']);
