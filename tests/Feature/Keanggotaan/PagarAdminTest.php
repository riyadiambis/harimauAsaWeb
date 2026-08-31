<?php

namespace Tests\Feature\Keanggotaan;

use App\Exceptions\HakAdminException;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * B-14: sistem harus selalu punya minimal satu Admin, dan Admin tidak boleh
 * melucuti dirinya sendiri.
 */
class PagarAdminTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $user = User::factory()->create();
        $user->is_admin = true;
        $user->save();

        return $user;
    }

    // --- Pencabutan hak sendiri ---------------------------------------------

    public function test_admin_tidak_bisa_mencabut_hak_admin_sendiri(): void
    {
        $this->admin();            // cadangan, supaya bukan admin terakhir
        $pelaku = $this->admin();

        $this->actingAs($pelaku);

        $this->expectException(HakAdminException::class);
        $this->expectExceptionMessage('tidak bisa dicabut sendiri');

        $pelaku->is_admin = false;
        $pelaku->save();
    }

    public function test_hak_admin_sendiri_tetap_utuh_setelah_percobaan_gagal(): void
    {
        $this->admin();
        $pelaku = $this->admin();
        $this->actingAs($pelaku);

        try {
            $pelaku->is_admin = false;
            $pelaku->save();
        } catch (HakAdminException) {
            // diabaikan, yang diperiksa keadaan akhirnya
        }

        $this->assertTrue($pelaku->fresh()->is_admin);
    }

    public function test_admin_lain_boleh_mencabut(): void
    {
        $pelaku = $this->admin();
        $target = $this->admin();

        $this->actingAs($pelaku);

        $target->is_admin = false;
        $target->save();

        $this->assertFalse($target->fresh()->is_admin);
    }

    // --- Admin terakhir ------------------------------------------------------

    public function test_admin_terakhir_tidak_bisa_dicabut(): void
    {
        $satunya = $this->admin();

        $this->expectException(HakAdminException::class);
        $this->expectExceptionMessage('Sistem harus selalu punya minimal satu admin');

        $satunya->is_admin = false;
        $satunya->save();
    }

    public function test_admin_terakhir_tidak_bisa_dihapus(): void
    {
        $satunya = $this->admin();

        $this->expectException(HakAdminException::class);

        $satunya->delete();
    }

    /**
     * Regresi: percobaan pencabutan yang gagal meninggalkan is_admin = false di
     * memori. Pagar penghapusan harus tetap membaca nilai tersimpan, bukan itu.
     */
    public function test_admin_terakhir_tetap_terjaga_setelah_pencabutan_gagal(): void
    {
        $satunya = $this->admin();

        try {
            $satunya->is_admin = false;
            $satunya->save();
        } catch (HakAdminException) {
            // memang diharapkan gagal
        }

        $this->expectException(HakAdminException::class);
        $this->expectExceptionMessage('tidak bisa dihapus');

        $satunya->delete();
    }

    public function test_admin_boleh_dihapus_kalau_masih_ada_admin_lain(): void
    {
        $this->admin();
        $target = $this->admin();

        $target->delete();

        $this->assertSoftDeleted($target);
    }

    /** Akun yang sudah di-soft-delete tidak boleh dihitung sebagai admin tersisa. */
    public function test_admin_yang_sudah_dihapus_tidak_dihitung(): void
    {
        $terhapus = $this->admin();
        $aktif = $this->admin();
        $terhapus->delete();

        $this->assertTrue($aktif->adminTerakhir());

        $this->expectException(HakAdminException::class);

        $aktif->is_admin = false;
        $aktif->save();
    }

    public function test_pemberian_hak_admin_tidak_terhalang(): void
    {
        $this->admin();
        $calon = User::factory()->create();

        $calon->is_admin = true;
        $calon->save();

        $this->assertTrue($calon->fresh()->is_admin);
    }

    public function test_bendera_lain_tidak_ikut_terjaga(): void
    {
        $admin = $this->admin();

        $admin->is_editor = true;
        $admin->save();
        $admin->is_editor = false;
        $admin->save();

        $this->assertFalse($admin->fresh()->is_editor);
    }

    // --- Policy --------------------------------------------------------------

    public function test_policy_menolak_pencabutan_diri_sendiri(): void
    {
        $this->admin();
        $pelaku = $this->admin();

        $this->assertFalse($pelaku->can('cabutHakAdmin', $pelaku));
    }

    public function test_policy_menolak_pencabutan_admin_terakhir(): void
    {
        $satunya = $this->admin();
        $pelaku = $this->admin();
        // Sekarang ada dua; hapus salah satu supaya $satunya jadi yang terakhir.
        $pelaku->forceDelete();

        $this->assertTrue($satunya->adminTerakhir());
    }

    public function test_policy_mengizinkan_pencabutan_yang_wajar(): void
    {
        $pelaku = $this->admin();
        $target = $this->admin();

        $this->assertTrue($pelaku->can('cabutHakAdmin', $target));
    }

    public function test_bukan_admin_tidak_bisa_mencabut(): void
    {
        $this->admin();
        $target = $this->admin();
        $bukanAdmin = User::factory()->create();

        $this->assertFalse($bukanAdmin->can('cabutHakAdmin', $target));
    }
}
