<?php

use App\Http\Controllers\Auth\CekUsernameController;
use App\Http\Controllers\Auth\DaftarController;
use App\Http\Controllers\Auth\GantiSandiController;
use App\Http\Controllers\Auth\MasukController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware('guest')->group(function () {
    Route::get('/daftar', [DaftarController::class, 'create'])->name('daftar');
    Route::post('/daftar', [DaftarController::class, 'store']);

    Route::get('/masuk', [MasukController::class, 'create'])->name('masuk');
    Route::post('/masuk', [MasukController::class, 'store'])->middleware('throttle:masuk');
});

// Di luar grup guest: halaman konfirmasi setelah daftar (A-4, tanpa auto-login).
Route::get('/daftar/selesai', [DaftarController::class, 'selesai'])->name('daftar.selesai');

Route::get('/cek-username', CekUsernameController::class)
    ->middleware('throttle:cek-username')
    ->name('cek-username');

// 'sandi.diganti' (A-8) hanya di zona yang butuh login. Rute ganti sandi dan
// keluar dikecualikan di dalam middleware-nya sendiri supaya tidak terkunci.
Route::middleware(['auth', 'sandi.diganti'])->group(function () {
    Route::get('/ganti-sandi', [GantiSandiController::class, 'edit'])->name('ganti-sandi.edit');
    Route::put('/ganti-sandi', [GantiSandiController::class, 'update'])->name('ganti-sandi.update');

    Route::post('/keluar', [MasukController::class, 'destroy'])->name('keluar');
});
