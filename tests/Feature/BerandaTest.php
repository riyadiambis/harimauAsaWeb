<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

/**
 * Beranda PUBLIK.
 *
 * Aplikasi ini web profil perguruan yang sekaligus memuat portal anggota; sisi
 * publiknya separuh alasan project ini ada (PRD bagian 1 dan bagian 3 nomor 4).
 * Yang dijaga tes ini: / tidak pernah menuntut login dan tidak pernah melempar
 * siapa pun ke tempat lain — termasuk pemegang hak akses ke /admin.
 */
class BerandaTest extends TestCase
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

    // --- Publik: tidak pernah menuntut login ---------------------------------

    public function test_tamu_bisa_membuka_tanpa_akun(): void
    {
        $this->get('/')
            ->assertSuccessful()
            ->assertSee('Pertalian Silat Harimau Asa');
    }

    /** Yang paling penting: tamu TIDAK dialihkan ke mana pun. */
    public function test_tamu_tidak_dialihkan_ke_mana_pun(): void
    {
        $respons = $this->get('/');

        $this->assertSame(200, $respons->getStatusCode());
        $this->assertNull($respons->headers->get('Location'));
    }

    /**
     * Rute / tidak boleh punya middleware auth. Diperiksa pada definisi rutenya,
     * bukan hanya lewat responsnya — supaya penambahan auth di kemudian hari
     * ketahuan walau kebetulan tidak mengubah perilaku tes lain.
     */
    public function test_rute_beranda_tidak_punya_middleware_auth(): void
    {
        $rute = app('router')->getRoutes()->getByName('beranda');

        $this->assertNotNull($rute);
        $this->assertNotContains('auth', $rute->gatherMiddleware());
    }

    // --- Isi halaman ---------------------------------------------------------

    public function test_menampilkan_nama_perguruan_dan_pengantar(): void
    {
        $this->get('/')
            ->assertSee('Pertalian Silat Harimau Asa')
            ->assertSee('Melak Ulu')
            ->assertSee('Kutai Barat')
            ->assertSee('2020');
    }

    /** design-tokens larangan 5: tidak boleh ada teks pengisi. */
    public function test_tanpa_teks_pengisi(): void
    {
        $isi = $this->get('/')->getContent();

        foreach (['Lorem', 'ipsum', 'dolor sit amet', 'Coming soon'] as $pengisi) {
            $this->assertStringNotContainsString($pengisi, $isi, $pengisi);
        }
    }

    /**
     * Beranda sungguhan dikerjakan fitur 11. Sesi ini sengaja tidak memasang
     * hero slider, galeri, maupun kartu pengumuman.
     */
    public function test_belum_memuat_bagian_yang_wilayah_fitur_11(): void
    {
        $isi = $this->get('/')->getContent();

        foreach (['hero', 'galeri', 'carousel', 'slider'] as $bagian) {
            $this->assertStringNotContainsStringIgnoringCase($bagian, $isi, $bagian);
        }
    }

    // --- Tautan masuk / panel ------------------------------------------------

    public function test_tamu_melihat_tautan_masuk(): void
    {
        $this->get('/')
            ->assertSee('Masuk')
            ->assertSee(route('masuk'), false);
    }

    public function test_tamu_tidak_melihat_tautan_panel(): void
    {
        $this->get('/')
            ->assertDontSee('Panel pengelola')
            ->assertDontSee('href="'.url('/admin').'"', false);
    }

    /** B-15: tautan panel hanya untuk pemegang minimal satu hak akses. */
    #[TestWith(['is_editor'])]
    #[TestWith(['is_guru_besar'])]
    #[TestWith(['is_sekben'])]
    #[TestWith(['is_admin'])]
    public function test_pemegang_hak_akses_melihat_tautan_panel(string $bendera): void
    {
        $user = $this->penggunaDengan([$bendera]);

        $this->actingAs($user)->get('/')
            ->assertSuccessful()
            ->assertSee('Panel pengelola')
            ->assertSee('href="'.url('/admin').'"', false);
    }

    public function test_anggota_biasa_tidak_melihat_tautan_panel(): void
    {
        $anggota = $this->penggunaDengan([]);

        $this->actingAs($anggota)->get('/')
            ->assertSuccessful()
            ->assertDontSee('Panel pengelola')
            ->assertDontSee('href="'.url('/admin').'"', false);
    }

    /**
     * Koreksi dari sesi sebelumnya: pemegang hak akses TIDAK lagi dilempar
     * otomatis ke /admin. Panel dicapai lewat tautan, bukan pengalihan.
     */
    public function test_pemegang_hak_akses_tidak_dilempar_ke_panel(): void
    {
        $gurubesar = $this->penggunaDengan(['is_guru_besar']);

        $respons = $this->actingAs($gurubesar)->get('/');

        $this->assertSame(200, $respons->getStatusCode());
        $this->assertNull($respons->headers->get('Location'));
    }

    /** Semua orang melihat halaman yang sama; yang berbeda hanya tautan pojoknya. */
    public function test_semua_orang_melihat_halaman_yang_sama(): void
    {
        $tamu = $this->get('/')->getContent();
        $anggota = $this->actingAs($this->penggunaDengan([]))->get('/')->getContent();
        $pengurus = $this->actingAs($this->penggunaDengan(['is_sekben']))->get('/')->getContent();

        foreach ([$tamu, $anggota, $pengurus] as $isi) {
            $this->assertStringContainsString('Pertalian Silat Harimau Asa', $isi);
            $this->assertStringContainsString('Melak Ulu', $isi);
        }
    }

    /** Yang sudah masuk tidak ditawari tautan masuk lagi. */
    public function test_yang_sudah_masuk_tidak_melihat_tautan_masuk(): void
    {
        $this->actingAs($this->penggunaDengan([]))->get('/')
            ->assertDontSee('>Masuk<', false);
    }

    /**
     * Halaman tunggu sesi lalu dihapus, jadi beranda ini satu-satunya tempat
     * anggota biasa mendarat — ia harus tetap punya jalan keluar.
     */
    public function test_yang_sudah_masuk_punya_jalan_keluar(): void
    {
        $this->actingAs($this->penggunaDengan([]))->get('/')
            ->assertSee('Keluar')
            ->assertSee(route('keluar'), false);
    }

    // --- Tidak ada loop ------------------------------------------------------

    /**
     * Loop yang menghantui rancangan sesi lalu tidak mungkin terjadi lagi: /
     * merender halaman, bukan mengalihkan, jadi pantulan dari middleware `guest`
     * di /masuk berhenti di sini.
     */
    public function test_tidak_ada_loop_pengalihan(): void
    {
        $anggota = $this->penggunaDengan([]);

        $this->actingAs($anggota)->get(route('masuk'))->assertRedirect('/');
        $this->actingAs($anggota)->get('/')->assertSuccessful();
    }
}
