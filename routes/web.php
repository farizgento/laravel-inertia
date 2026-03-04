<?php

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


Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->name('dashboard');

Route::get('/peminjaman', function () {
    return Inertia::render('User/Peminjaman');
})->name('peminjaman.create');

Route::get('/riwayat-peminjaman', function () {
    return Inertia::render('User/History');
})->name('peminjaman.history');

Route::get('/mutasi-alat', function () {
    return Inertia::render('User/MutasiAlat');
})->name('mutasi-alat');

Route::get('/review-peminjaman', function () {
    return Inertia::render('Sptool/Index');
})->name('review.peminjaman');

Route::get('/master-alat', function () {
    return Inertia::render('Pictool/Index');
})->name('master-alat');

Route::get('persiapan-alat', function (){
    return redirect()->route('pengiriman-alat');
})->name('persiapan-alat');

Route::get('pengiriman-alat', function (){
    return Inertia::render('Pictool/Pengiriman');
})->name('pengiriman-alat');

Route::get('/riwayat-pengiriman', function () {
    return Inertia::render('Pictool/RiwayatPengiriman');
})->name('riwayat-pengiriman');


Route::get('/test-oracle', function () {
    return DB::connection('oracle')->select('select 1 from dual');
});
