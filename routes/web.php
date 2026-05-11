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

Route::get('/forgot-password', function () {
    return Inertia::render('Auth/ForgotPassword');
})->name('password.request');

Route::get('/reset-password/{token}', function (string $token) {
    return Inertia::render('Auth/ResetPassword', [
        'token' => $token,
        'email' => request('email', ''),
    ]);
})->name('password.reset');

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
        Role::KEY_ADMIN,
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
        Role::KEY_ADMIN,
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
        return Inertia::render('Pictool/Pengiriman', [
            'shippingCategory' => 'Intra Area',
            'shippingPageTitle' => 'Transaksi Intra Area',
            'shippingPageSubtitle' => 'Kelola pengiriman dan pengembalian peminjaman alat intra area',
            'shippingActiveMenu' => 'pengiriman',
        ]);
    })->middleware('role:' . implode(',', [
        Role::KEY_ADMIN,
        Role::KEY_SUPER_ADMIN,
        Role::KEY_PIC_TOOLS,
    ]))->name('pengiriman-alat');

    Route::get('pengiriman-antar-area', function () {
        return Inertia::render('Pictool/Pengiriman', [
            'shippingCategory' => 'Antar Area',
            'shippingPageTitle' => 'Transaksi Antar Area',
            'shippingPageSubtitle' => 'Kelola peminjaman, pengiriman, dan pengembalian alat antar area',
            'shippingActiveMenu' => 'pengiriman-antar-area',
        ]);
    })->middleware('role:' . implode(',', [
        Role::KEY_ADMIN,
        Role::KEY_SUPER_ADMIN,
        Role::KEY_PIC_TOOLS,
    ]))->name('pengiriman-antar-area');

    Route::get('pengembalian-antar-area', function () {
        return redirect()->route('pengiriman-antar-area');
    })->middleware('role:' . implode(',', [
        Role::KEY_ADMIN,
        Role::KEY_SUPER_ADMIN,
        Role::KEY_PIC_TOOLS,
    ]))->name('pengembalian-antar-area');

    Route::get('peminjaman-antar-area', function () {
        return Inertia::render('Pictool/PeminjamanAntarArea');
    })->middleware('role:' . implode(',', [
        Role::KEY_ADMIN,
        Role::KEY_SUPER_ADMIN,
        Role::KEY_PIC_TOOLS,
    ]))->name('peminjaman-antar-area');

    Route::get('pengembalian-alat', function () {
        return redirect()->route('pengiriman-alat');
    })->middleware('role:' . implode(',', [
        Role::KEY_ADMIN,
        Role::KEY_SUPER_ADMIN,
        Role::KEY_PIC_TOOLS,
    ]))->name('pengembalian-alat');

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

    Route::get('/log-activity', function () {
        return Inertia::render('Admin/ActivityLog');
    })->middleware('role:' . implode(',', [
        Role::KEY_SP_TOOL,
        Role::KEY_MGR_TOOL,
        Role::KEY_ADMIN,
        Role::KEY_SUPER_ADMIN,
    ]))->name('activity-log');

    Route::get('/log-alat', function () {
        return Inertia::render('Admin/AlatLog');
    })->middleware('role:' . implode(',', [
        Role::KEY_SP_TOOL,
        Role::KEY_PIC_TOOLS,
        Role::KEY_MGR_TOOL,
        Role::KEY_ADMIN,
        Role::KEY_SUPER_ADMIN,
    ]))->name('alat-log');

    Route::get('/area', function () {
        return Inertia::render('Admin/Area');
    })->middleware('role:' . Role::KEY_SUPER_ADMIN)->name('area.index');
});


Route::get('/test-oracle', function () {
    return DB::connection('oracle')->select('select 1 from dual');
});
