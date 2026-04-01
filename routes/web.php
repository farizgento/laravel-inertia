<?php

use App\Models\Role;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/login', function () {
    return Inertia::render('Auth/Login');
})->name('login');

Route::get('/register', function () {
    return Inertia::render('Auth/Register');
})->name('register');

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/dashboard', function () {
        return Inertia::render('Dashboard');
    })->middleware('role:' . implode(',', [
        Role::KEY_USER,
        Role::KEY_SP_TOOL,
        Role::KEY_PIC_TOOLS,
        Role::KEY_MGR_TOOL,
        Role::KEY_ADMIN,
        Role::KEY_SUPER_ADMIN,
    ]))->name('dashboard');

    Route::get('/peminjaman', function () {
        return Inertia::render('User/Peminjaman');
    })->middleware('role:' . implode(',', [
        Role::KEY_USER,
        Role::KEY_SUPER_ADMIN,
    ]))->name('peminjaman.create');

    Route::get('/riwayat-peminjaman', function () {
        return Inertia::render('User/History');
    })->middleware('role:' . implode(',', [
        Role::KEY_USER,
        Role::KEY_SP_TOOL,
        Role::KEY_PIC_TOOLS,
        Role::KEY_MGR_TOOL,
        Role::KEY_ADMIN,
        Role::KEY_SUPER_ADMIN,
    ]))->name('peminjaman.history');

    Route::get('/mutasi-alat', function () {
        return Inertia::render('User/MutasiAlat');
    })->middleware('role:' . implode(',', [
        Role::KEY_USER,
        Role::KEY_MGR_TOOL,
        Role::KEY_SUPER_ADMIN,
    ]))->name('mutasi-alat');

    Route::get('/review-peminjaman', function () {
        return Inertia::render('Sptool/Index');
    })->middleware('role:' . implode(',', [
        Role::KEY_SP_TOOL,
        Role::KEY_MGR_TOOL,
        Role::KEY_SUPER_ADMIN,
    ]))->name('review.peminjaman');

    Route::get('/master-alat', function () {
        return Inertia::render('Pictool/Index');
    })->middleware('role:' . implode(',', [
        Role::KEY_SP_TOOL,
        Role::KEY_PIC_TOOLS,
        Role::KEY_MGR_TOOL,
        Role::KEY_SUPER_ADMIN,
    ]))->name('master-alat');

    Route::get('persiapan-alat', function () {
        return redirect()->route('pengiriman-alat');
    })->middleware('role:' . implode(',', [
        Role::KEY_ADMIN,
        Role::KEY_SUPER_ADMIN,
        Role::KEY_PIC_TOOLS,
    ]))->name('persiapan-alat');

    Route::get('pengiriman-alat', function () {
        return Inertia::render('Pictool/Pengiriman');
    })->middleware('role:' . implode(',', [
        Role::KEY_ADMIN,
        Role::KEY_SUPER_ADMIN,
        Role::KEY_PIC_TOOLS,
    ]))->name('pengiriman-alat');

    Route::get('/riwayat-pengiriman', function () {
        return Inertia::render('Pictool/RiwayatPengiriman');
    })->middleware('role:' . implode(',', [
        Role::KEY_SP_TOOL,
        Role::KEY_PIC_TOOLS,
        Role::KEY_ADMIN,
        Role::KEY_SUPER_ADMIN,
    ]))->name('riwayat-pengiriman');

    Route::get('/laporan-kerusakan', function () {
        return Inertia::render('Pictool/Kerusakan');
    })->middleware('role:' . implode(',', [
        Role::KEY_SP_TOOL,
        Role::KEY_PIC_TOOLS,
        Role::KEY_MGR_TOOL,
        Role::KEY_ADMIN,
        Role::KEY_SUPER_ADMIN,
    ]))->name('laporan-kerusakan');

    Route::get('/laporan-kehilangan', function () {
        return Inertia::render('Pictool/Kehilangan');
    })->middleware('role:' . implode(',', [
        Role::KEY_SP_TOOL,
        Role::KEY_PIC_TOOLS,
        Role::KEY_MGR_TOOL,
        Role::KEY_ADMIN,
        Role::KEY_SUPER_ADMIN,
    ]))->name('laporan-kehilangan');

    Route::get('/tambah-pengguna', function () {
        return Inertia::render('Admin/TambahPengguna');
    })->middleware('role:' . implode(',', [
        Role::KEY_ADMIN,
        Role::KEY_SUPER_ADMIN,
    ]))->name('tambah-pengguna');

    Route::get('/area', function () {
        return Inertia::render('Admin/Area');
    })->middleware('role:' . Role::KEY_SUPER_ADMIN)->name('area.index');
});


Route::get('/test-oracle', function () {
    return DB::connection('oracle')->select('select 1 from dual');
});
