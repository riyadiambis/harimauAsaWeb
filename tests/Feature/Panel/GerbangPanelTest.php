<?php

namespace Tests\Feature\Panel;

use App\Models\User;
use Filament\Facades\Filament;
use Filament\Panel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

/**
 * B-15: gerbang masuk panel pengelola /admin.
 *
 * Yang diuji di sini hanya siapa yang boleh lewat pintu — bukan apa yang boleh
 * dilakukan setelah masuk. Itu tetap urusan policy B-2, B-4, B-5, B-6 yang
 * diuji di tests/Feature/Keanggotaan/HakAksesTest.php.
 */
class GerbangPanelTest extends TestCase
{
    use RefreshDatabase;

    private function panel(): Panel
    {
        return Filament::getPanel('admin');
    }

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

    // --- Ditolak -------------------------------------------------------------

    public function test_anggota_biasa_ditolak(): void
    {
        $anggota = $this->penggunaDengan([]);

        $this->assertFalse($anggota->canAccessPanel($this->panel()));
    }

    /** Keempat benderanya false, bukan null — pastikan yang diuji memang itu. */
    public function test_anggota_biasa_memang_tidak_punya_bendera(): void
    {
        $anggota = $this->penggunaDengan([]);

        foreach (['is_editor', 'is_guru_besar', 'is_sekben', 'is_admin'] as $b) {
            $this->assertFalse($anggota->{$b}, $b);
        }
    }

    // --- Diterima ------------------------------------------------------------

    public function test_editor_diterima(): void
    {
        $editor = $this->penggunaDengan(['is_editor']);

        $this->assertTrue($editor->canAccessPanel($this->panel()));
    }

    /** Satu bendera apa pun sudah cukup untuk lewat pintu. */
    #[TestWith(['is_editor'])]
    #[TestWith(['is_guru_besar'])]
    #[TestWith(['is_sekben'])]
    #[TestWith(['is_admin'])]
    public function test_satu_bendera_apa_pun_cukup(string $bendera): void
    {
        $user = $this->penggunaDengan([$bendera]);

        $this->assertTrue($user->canAccessPanel($this->panel()), $bendera);
    }

    public function test_lebih_dari_satu_hak_akses_diterima(): void
    {
        $rangkap = $this->penggunaDengan(['is_sekben', 'is_admin']);

        $this->assertTrue($rangkap->canAccessPanel($this->panel()));
    }

    public function test_keempat_hak_akses_sekaligus_diterima(): void
    {
        $semua = $this->penggunaDengan(['is_editor', 'is_guru_besar', 'is_sekben', 'is_admin']);

        $this->assertTrue($semua->canAccessPanel($this->panel()));
    }

    // --- Pencabutan menutup pintu lagi ---------------------------------------

    public function test_hak_yang_dicabut_menutup_pintu(): void
    {
        $editor = $this->penggunaDengan(['is_editor']);
        $this->assertTrue($editor->canAccessPanel($this->panel()));

        $editor->is_editor = false;
        $editor->save();

        $this->assertFalse($editor->fresh()->canAccessPanel($this->panel()));
    }

    // --- Lewat HTTP ----------------------------------------------------------

    public function test_tamu_diarahkan_ke_halaman_masuk(): void
    {
        $this->get('/admin')->assertRedirect(route('masuk'));
    }

    public function test_anggota_biasa_ditolak_di_http(): void
    {
        $anggota = $this->penggunaDengan([]);

        $this->actingAs($anggota)->get('/admin')->assertForbidden();
    }

    public function test_editor_bisa_membuka_panel(): void
    {
        $editor = $this->penggunaDengan(['is_editor']);

        $this->actingAs($editor)->get('/admin')->assertSuccessful();
    }

    /**
     * Panel tidak boleh punya halaman masuk sendiri — A-6, A-12, A-9, dan A-8
     * semuanya dijaga MasukController di /masuk, dan pintu kedua akan
     * melewatinya.
     */
    public function test_panel_tidak_punya_halaman_masuk_sendiri(): void
    {
        $this->assertFalse($this->panel()->hasLogin());
        $this->get('/admin/login')->assertNotFound();
    }
}
