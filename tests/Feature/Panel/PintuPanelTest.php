<?php

namespace Tests\Feature\Panel;

use App\Http\Controllers\Auth\MasukController;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Pintu keluar-masuk panel: bagaimana tamu diarahkan, bagaimana yang berhak
 * menemukan jalannya, dan lewat mana sesi diakhiri.
 *
 * Siapa yang boleh masuk (B-15) diuji terpisah di GerbangPanelTest.
 */
class PintuPanelTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @param  array<int, string>  $bendera
     */
    private function penggunaDengan(array $bendera): User
    {
        $user = User::factory()->create();

        foreach ($bendera as $b) {
            $user->{$b} = true;
        }

        $user->save();

        return $user->fresh();
    }

    // --- 1. Tamu yang membuka /admin -----------------------------------------

    /**
     * Panel sengaja tidak punya halaman masuk sendiri. Tanpa itu, pengalihan
     * tamu bergantung pada redirectGuestsTo() di bootstrap/app.php — dan bukan
     * pada route login Filament yang memang tidak terdaftar.
     */
    public function test_panel_memang_tidak_punya_halaman_masuk(): void
    {
        $this->assertFalse(Filament::getPanel('admin')->hasLogin());
        $this->assertNull(Filament::getPanel('admin')->getLoginUrl());
    }

    public function test_tamu_dialihkan_ke_masuk_bukan_error(): void
    {
        $respons = $this->get('/admin');

        $respons->assertStatus(302);
        $respons->assertRedirect(route('masuk'));
    }

    /** Bukan 500, bukan 404 — dua gejala kalau route login Filament dicari. */
    public function test_tamu_tidak_mendapat_galat_server(): void
    {
        $this->withExceptionHandling();

        $respons = $this->get('/admin');

        $this->assertNotSame(500, $respons->getStatusCode());
        $this->assertNotSame(404, $respons->getStatusCode());
    }

    public function test_halaman_masuk_yang_dituju_benar_benar_terbuka(): void
    {
        $this->get('/admin')
            ->assertRedirect(route('masuk'));

        $this->get(route('masuk'))->assertSuccessful();
    }

    // --- 2. Jalan menuju panel -----------------------------------------------
    //
    // Tautan menuju /admin di beranda diuji di tests/Feature/BerandaTest.php,
    // bersama sisa perilaku halaman publik itu.

    /**
     * Tautan dan pintu memakai pemeriksaan yang sama, jadi tidak mungkin ada
     * orang yang melihat tautan panel tapi ditolak di pintunya, atau sebaliknya.
     */
    public function test_tautan_dan_pintu_sepakat(): void
    {
        $panel = Filament::getPanel('admin');

        foreach ([[], ['is_editor'], ['is_sekben', 'is_admin']] as $bendera) {
            $user = $this->penggunaDengan($bendera);

            $this->assertSame(
                $user->punyaHakAkses(),
                $user->canAccessPanel($panel),
                implode('+', $bendera) ?: 'tanpa hak akses',
            );
        }
    }

    // --- 3. Keluar dari dalam panel ------------------------------------------

    /**
     * Item "keluar" di menu pengguna panel harus menunjuk /keluar milik
     * aplikasi, bukan POST /admin/logout bawaan Filament.
     */
    public function test_tombol_keluar_panel_menunjuk_rute_aplikasi(): void
    {
        $admin = $this->penggunaDengan(['is_admin']);
        $this->actingAs($admin);

        $item = collect(Filament::getPanel('admin')->getUserMenuItems())
            ->first(fn ($item): bool => $item->getName() === 'logout');

        $this->assertNotNull($item, 'item logout tidak ditemukan di menu pengguna');
        $this->assertSame(route('keluar'), $item->getUrl());
        $this->assertNotSame(route('filament.admin.auth.logout'), $item->getUrl());
        $this->assertTrue($item->shouldPostToUrl(), 'keluar harus lewat POST, bukan GET');
    }

    public function test_keluar_lewat_rute_aplikasi_mengakhiri_sesi(): void
    {
        $admin = $this->penggunaDengan(['is_admin']);

        $this->actingAs($admin)
            ->post(route('keluar'))
            ->assertRedirect(route('masuk'));

        $this->assertGuest();
    }

    /** Sesudah keluar, panel tertutup lagi. */
    public function test_panel_tertutup_lagi_sesudah_keluar(): void
    {
        $admin = $this->penggunaDengan(['is_admin']);

        $this->actingAs($admin)->get('/admin')->assertSuccessful();

        $this->post(route('keluar'));

        $this->get('/admin')->assertRedirect(route('masuk'));
    }

    /**
     * Yang sesungguhnya dijaga: HTML panel yang dirender tidak boleh memuat
     * satu pun form ke jalur keluar milik Filament.
     *
     * Regresi yang pernah terjadi: override userMenuItems sudah benar, tapi
     * AccountWidget merender tombol keluarnya sendiri langsung ke
     * filament()->getLogoutUrl() di view-nya — pintu keluar kedua yang lolos
     * dari pemeriksaan di tes atas.
     */
    public function test_panel_tidak_merender_jalur_keluar_filament(): void
    {
        $admin = $this->penggunaDengan(['is_admin']);

        $html = $this->actingAs($admin)->get('/admin')->getContent();

        $this->assertStringContainsString(route('keluar'), $html);
        $this->assertStringNotContainsString('admin/logout', $html);
    }

    // --- Rute keluar milik Filament ditimpa -----------------------------------

    /**
     * POST /admin/logout milik Filament ditimpa rute bernama sama yang menunjuk
     * MasukController::destroy, jadi tidak ada rute Filament yang menganggur
     * dengan jalur keluar sendiri.
     */
    public function test_rute_logout_filament_menunjuk_controller_aplikasi(): void
    {
        $rute = app('router')->getRoutes()->getByName('filament.admin.auth.logout');

        $this->assertNotNull($rute);
        $this->assertSame(
            MasukController::class.'@destroy',
            $rute->getAction('controller'),
        );
    }

    public function test_rute_logout_filament_mengakhiri_sesi(): void
    {
        $admin = $this->penggunaDengan(['is_admin']);

        $this->actingAs($admin)
            ->post('/admin/logout')
            ->assertRedirect(route('masuk'));

        $this->assertGuest();
    }

    /** Keduanya harus mendarat di tempat yang sama. */
    public function test_kedua_jalur_keluar_mendarat_di_tempat_sama(): void
    {
        $lewatPanel = $this->actingAs($this->penggunaDengan(['is_admin']))
            ->post('/admin/logout');
        $this->assertGuest();

        $lewatAplikasi = $this->actingAs($this->penggunaDengan(['is_admin']))
            ->post(route('keluar'));
        $this->assertGuest();

        $this->assertSame(
            $lewatAplikasi->headers->get('Location'),
            $lewatPanel->headers->get('Location'),
        );
    }

    /** Tamu tidak bisa memanggilnya — middleware auth tetap terpasang. */
    public function test_tamu_tidak_bisa_memanggil_rute_keluar_panel(): void
    {
        $this->post('/admin/logout')->assertRedirect(route('masuk'));
    }
}
