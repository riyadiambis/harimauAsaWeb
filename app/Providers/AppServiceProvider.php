<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->batasiPercobaan();
    }

    /**
     * Batas laju untuk alur autentikasi.
     */
    private function batasiPercobaan(): void
    {
        // A-9: 5 percobaan masuk per menit per IP.
        RateLimiter::for('masuk', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip())->response(function () {
                return back()->withErrors([
                    'username' => 'Terlalu banyak percobaan masuk. Coba lagi dalam satu menit.',
                ]);
            });
        });

        // Endpoint cek ketersediaan username memang harus menjawab "sudah dipakai
        // atau belum" (A-1), jadi ia membocorkan username mana yang terdaftar.
        // Dibatasi supaya tidak praktis dipakai memanen daftar username.
        RateLimiter::for('cek-username', function (Request $request) {
            return Limit::perMinute(30)->by($request->ip());
        });
    }
}
