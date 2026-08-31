<?php

namespace Tests\Feature\Keanggotaan;

use App\Models\Jabatan;
use App\Models\Member;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HakAksesTest extends TestCase
{
    use RefreshDatabase;

    private function pengguna(string ...$bendera): User
    {
        $user = User::factory()->create();

        foreach ($bendera as $b) {
            $user->{$b} = true;
        }

        $user->save();

        return $user;
    }

    // --- B-2: tingkat, sabuk, no warga ---------------------------------------

    /** Skenario 3: Admin murni tidak boleh mengubah sabuk. */
    public function test_admin_tidak_boleh_ubah_tingkat_dan_sabuk(): void
    {
        $this->assertFalse(
            $this->pengguna('is_admin')->can('ubahTingkatDanSabuk', Member::factory()->create())
        );
    }

    public function test_guru_besar_dan_sekben_boleh_ubah_tingkat_dan_sabuk(): void
    {
        $member = Member::factory()->create();

        $this->assertTrue($this->pengguna('is_guru_besar')->can('ubahTingkatDanSabuk', $member));
        $this->assertTrue($this->pengguna('is_sekben')->can('ubahTingkatDanSabuk', $member));
    }

    /** B-2: "kecuali dia juga memegang salah satu jabatan itu". */
    public function test_admin_yang_juga_sekben_boleh_ubah_sabuk(): void
    {
        $this->assertTrue(
            $this->pengguna('is_admin', 'is_sekben')->can('ubahTingkatDanSabuk', Member::factory()->create())
        );
    }

    public function test_anggota_biasa_tidak_boleh_ubah_sabuk(): void
    {
        $this->assertFalse(
            $this->pengguna()->can('ubahTingkatDanSabuk', Member::factory()->create())
        );
    }

    // --- B-5: status & persetujuan pendaftar ---------------------------------

    public function test_hanya_guru_besar_dan_sekben_boleh_ubah_status(): void
    {
        $member = Member::factory()->create();

        $this->assertTrue($this->pengguna('is_guru_besar')->can('ubahStatus', $member));
        $this->assertTrue($this->pengguna('is_sekben')->can('ubahStatus', $member));
        $this->assertFalse($this->pengguna('is_admin')->can('ubahStatus', $member));
        $this->assertFalse($this->pengguna('is_editor')->can('ubahStatus', $member));
    }

    public function test_hanya_pendaftar_pending_yang_bisa_disetujui(): void
    {
        $guruBesar = $this->pengguna('is_guru_besar');

        $this->assertTrue($guruBesar->can('setujui', Member::factory()->create(['status' => 'pending'])));
        $this->assertFalse($guruBesar->can('setujui', Member::factory()->aktif()->create()));
    }

    // --- B-4: jabatan --------------------------------------------------------

    public function test_hanya_guru_besar_dan_sekben_boleh_kelola_jabatan(): void
    {
        $jabatan = Jabatan::factory()->create();

        foreach (['is_guru_besar', 'is_sekben'] as $bendera) {
            $user = $this->pengguna($bendera);
            $this->assertTrue($user->can('create', Jabatan::class), $bendera);
            $this->assertTrue($user->can('update', $jabatan), $bendera);
            $this->assertTrue($user->can('delete', $jabatan), $bendera);
        }

        $admin = $this->pengguna('is_admin');
        $this->assertFalse($admin->can('create', Jabatan::class));
        $this->assertFalse($admin->can('update', $jabatan));
        $this->assertFalse($admin->can('delete', $jabatan));
    }

    // --- B-6: hak akses ------------------------------------------------------

    /** Skenario 4: Guru Besar tidak boleh mengubah is_admin orang lain. */
    public function test_guru_besar_tidak_boleh_ubah_hak_akses(): void
    {
        $this->assertFalse(
            $this->pengguna('is_guru_besar')->can('ubahHakAkses', User::factory()->create())
        );
    }

    public function test_hanya_admin_boleh_ubah_hak_akses(): void
    {
        $target = User::factory()->create();

        $this->assertTrue($this->pengguna('is_admin')->can('ubahHakAkses', $target));
        $this->assertFalse($this->pengguna('is_sekben')->can('ubahHakAkses', $target));
        $this->assertFalse($this->pengguna('is_editor')->can('ubahHakAkses', $target));
        $this->assertFalse($this->pengguna()->can('ubahHakAkses', $target));
    }

    /** A-7: reset kata sandi boleh Guru Besar, Sekben, atau Admin. */
    public function test_reset_sandi_boleh_tiga_peran(): void
    {
        $target = User::factory()->create();

        foreach (['is_guru_besar', 'is_sekben', 'is_admin'] as $bendera) {
            $this->assertTrue($this->pengguna($bendera)->can('resetSandi', $target), $bendera);
        }

        $this->assertFalse($this->pengguna('is_editor')->can('resetSandi', $target));
    }
}
