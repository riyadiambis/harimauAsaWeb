<?php

use App\Http\Controllers\Auth\CekUsernameController;
use App\Http\Controllers\Auth\DaftarController;
use App\Http\Controllers\Auth\GantiSandiController;
use App\Http\Controllers\Auth\MasukController;
use Illuminate\Support\Facades\Route;

// Beranda PUBLIK. Tidak ada middleware auth di sini, dan jangan ditambahkan.
//
// Aplikasi ini web profil perguruan yang sekaligus memuat portal anggota —
// sisi publiknya separuh alasan project ini ada (PRD bagian 1 dan bagian 3
// nomor 4), bukan pelengkap di belakang login. Peta situs PRD bagian 7 menaruh
// /, /profil, /struktur, /artikel, /pengumuman, /galeri, /daftar, dan /masuk
// di zona publik. Zona anggota dan /admin yang butuh login, bukan sebaliknya.
//
// Tidak ada pengalihan otomatis ke /admin bagi pemegang hak akses: semua orang
// melihat halaman yang sama, dan panel dicapai lewat tautan. Melempar orang
// keluar dari halaman publik menanamkan asumsi bahwa aplikasi ini tertutup, dan
// asumsi itu akan merembet saat halaman publik lain dikerjakan.
//
// TODO fitur 11: ganti dengan beranda publik sungguhan (hero slider,
// pengumuman, artikel, galeri). View-nya ikut diganti utuh.
Route::view('/', 'beranda')->name('beranda');

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

// Menimpa POST /admin/logout milik Filament dengan jalur keluar aplikasi.
//
// Menu pengguna panel sudah diarahkan ke /keluar lewat userMenuItems(), tapi
// rute Filament-nya tetap terdaftar dan menganggur — jalur keluar kedua yang
// memanggil controllernya sendiri. Nama rutenya sengaja disamakan supaya
// filament()->getLogoutUrl() ikut menunjuk ke sini, termasuk dari view bawaan
// Filament yang memanggilnya langsung.
//
// Didaftarkan paling akhir: rute yang belakangan menang, baik untuk pencocokan
// URI maupun untuk route() berdasarkan nama.
Route::post('/admin/logout', [MasukController::class, 'destroy'])
    ->middleware('auth')
    ->name('filament.admin.auth.logout');
