<?php

namespace Tests\Feature\Panel;

use App\Filament\Resources\Anggota\AnggotaResource;
use App\Http\Middleware\PastikanSandiSudahDiganti;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

/**
 * A-8 berlaku di panel pengelola, bukan hanya di zona anggota.
 *
 * Sandi sementara A-7 disampaikan lewat WhatsApp — melewati pihak ketiga dan
 * bisa tertinggal di riwayat chat. A-8 memperpendek umurnya, dan pemegang hak
 * akses justru yang paling berbahaya kalau akunnya diambil orang.
 */
class GantiSandiPanelTest extends TestCase
{
    use RefreshDatabase;

    private function pengurusTerkunci(): User
    {
        $user = User::factory()->create();
        $user->is_sekben = true;
        $user->harus_ganti_sandi = true;
        $user->save();

        return $user->fresh();
    }

    // --- Terkunci sampai sandinya diganti ------------------------------------

    #[TestWith(['/admin'])]
    #[TestWith(['/admin/anggota'])]
    public function test_pengurus_terkunci_dialihkan_ke_ganti_sandi(string $jalur): void
    {
        $this->actingAs($this->pengurusTerkunci())
            ->get($jalur)
            ->assertRedirect(route('ganti-sandi.edit'));
    }

    /** Bukan cuma /admin — seluruh rute panel ikut terkunci. */
    public function test_halaman_rincian_dan_wilayah_ikut_terkunci(): void
    {
        $pengurus = $this->pengurusTerkunci();

        foreach (['/admin/wilayahs', '/admin/rantings'] as $jalur) {
            $this->actingAs($pengurus)
                ->get($jalur)
                ->assertRedirect(route('ganti-sandi.edit'), $jalur);
        }
    }

    // --- Jalan keluarnya tetap terbuka ---------------------------------------

    /** Tanpa ini pengurus terkunci total: tidak bisa masuk, tidak bisa keluar. */
    public function test_halaman_ganti_sandi_tetap_bisa_dibuka(): void
    {
        $this->actingAs($this->pengurusTerkunci())
            ->get(route('ganti-sandi.edit'))
            ->assertSuccessful();
    }

    public function test_pengurus_terkunci_tetap_bisa_keluar_lewat_rute_aplikasi(): void
    {
        $this->actingAs($this->pengurusTerkunci())
            ->post(route('keluar'))
            ->assertRedirect(route('masuk'));

        $this->assertGuest();
    }

    /**
     * POST /admin/logout hidup di routes/web.php di luar grup panel, jadi
     * middleware panel tidak menyentuhnya. Diuji supaya kalau suatu saat rute
     * itu dipindah ke dalam grup panel, kuncinya langsung ketahuan.
     */
    public function test_pengurus_terkunci_tetap_bisa_keluar_lewat_rute_panel(): void
    {
        $this->actingAs($this->pengurusTerkunci())
            ->post('/admin/logout')
            ->assertRedirect(route('masuk'));

        $this->assertGuest();
    }

    // --- Sesudah sandinya diganti --------------------------------------------

    public function test_panel_terbuka_normal_sesudah_sandi_diganti(): void
    {
        $pengurus = $this->pengurusTerkunci();

        $this->actingAs($pengurus)
            ->put(route('ganti-sandi.update'), [
                'password' => 'sandibarusaya',
                'password_confirmation' => 'sandibarusaya',
            ]);

        $this->assertFalse($pengurus->fresh()->harus_ganti_sandi);

        $this->actingAs($pengurus->fresh())->get('/admin')->assertSuccessful();
        $this->actingAs($pengurus->fresh())
            ->get(AnggotaResource::getUrl('index'))
            ->assertSuccessful();
    }

    /** Pengurus yang tidak sedang terkunci tidak terganggu sama sekali. */
    public function test_pengurus_biasa_tidak_terpengaruh(): void
    {
        $pengurus = User::factory()->create();
        $pengurus->is_sekben = true;
        $pengurus->save();

        $this->assertFalse($pengurus->fresh()->harus_ganti_sandi);

        $this->actingAs($pengurus->fresh())->get('/admin')->assertSuccessful();
    }

    // --- Middleware benar-benar terpasang ------------------------------------

    /**
     * Diperiksa pada definisi rutenya, bukan hanya lewat responsnya — supaya
     * pencabutan middleware di kemudian hari ketahuan walau kebetulan tidak
     * mengubah hasil tes lain.
     */
    public function test_rute_panel_memuat_middleware_sandi_diganti(): void
    {
        $rute = app('router')->getRoutes()
            ->getByName('filament.admin.resources.anggota.index');

        $this->assertNotNull($rute);
        $this->assertContains(
            PastikanSandiSudahDiganti::class,
            $rute->gatherMiddleware(),
        );
    }
}
