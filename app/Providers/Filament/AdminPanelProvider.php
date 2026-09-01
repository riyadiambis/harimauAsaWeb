<?php

namespace App\Providers\Filament;

use Filament\Actions\Action;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Filament\Widgets\FilamentInfoWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Blade;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            // ->login() SENGAJA TIDAK DIPASANG. Aplikasi ini sudah punya satu
            // pintu masuk di /masuk, dan pintu itu yang memegang A-6 (akun
            // pending), A-12 (non_aktif & alumni), A-9 (batas percobaan), serta
            // A-8 (paksa ganti sandi). Mengaktifkan halaman masuk milik Filament
            // membuat pintu kedua yang tidak memeriksa satu pun dari itu, dan
            // sesi yang lahir di sana berlaku untuk seluruh aplikasi.
            //
            // Tamu yang membuka /admin diarahkan ke /masuk oleh
            // redirectGuestsTo() di bootstrap/app.php.
            ->colors([
                'primary' => Color::Amber,
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            // AccountWidget SENGAJA TIDAK dipasang. Widget bawaan itu merender
            // tombol keluarnya sendiri langsung ke filament()->getLogoutUrl()
            // di dalam view-nya, jadi ia tidak ikut override userMenuItems di
            // bawah dan menjadi pintu keluar kedua lewat POST /admin/logout.
            // getLogoutUrl() dipatok ke rute auth.logout panel dan tidak punya
            // hook konfigurasi, jadi mencabut widgetnya yang paling bersih.
            ->widgets([
                FilamentInfoWidget::class,
            ])
            // Tombol keluar di panel memakai /keluar milik aplikasi, bukan
            // POST /admin/logout bawaan Filament. Keduanya sama-sama mengakhiri
            // sesi hari ini, tapi jalur Filament memanggil controller-nya
            // sendiri — begitu MasukController::destroy() dapat tambahan
            // (audit log keluar, pembersihan kode unik pembayaran), panel akan
            // diam-diam melewatinya.
            //
            // Closure-nya menerima Action bawaan dan hanya menukar URL-nya,
            // jadi label, ikon, dan ->postToUrl() Filament tetap terpakai.
            // design-tokens: JetBrains Mono khusus angka, kode unik, NIA, dan
            // no warga. Filament merender ->fontFamily(FontFamily::Mono) sebagai
            // .fi-font-mono yang membaca var(--mono-font-family), jadi cukup
            // mengarahkan variabel itu.
            //
            // Berkas fontnya di-host sendiri lewat plugin Vite (vite.config.js) —
            // mini PC ini tidak selalu punya jalur keluar, jadi jangan
            // bergantung pada CDN. @fonts menangani dev maupun hasil build.
            ->renderHook(
                PanelsRenderHook::HEAD_END,
                fn (): string => Blade::render('@fonts')
                    ."<style>:root{--mono-font-family:'JetBrains Mono';}</style>",
            )
            ->userMenuItems([
                'logout' => fn (Action $action): Action => $action->url(route('keluar')),
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
