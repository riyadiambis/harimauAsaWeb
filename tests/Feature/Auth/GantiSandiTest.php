<?php

namespace Tests\Feature\Auth;

use App\Models\Member;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class GantiSandiTest extends TestCase
{
    use RefreshDatabase;

    private function akunDireset(): User
    {
        $user = User::factory()->create([
            'username' => 'fikri_r',
            'password' => 'sandi-sementara',
        ]);

        $user->harus_ganti_sandi = true;
        $user->save();

        Member::factory()->for($user)->aktif()->create();

        return $user;
    }

    /** Skenario 6. */
    public function test_akun_yang_direset_dipaksa_ke_halaman_ganti_sandi(): void
    {
        $this->actingAs($this->akunDireset())
            ->get('/')
            ->assertRedirect(route('ganti-sandi.edit'));
    }

    /** Tanpa pengecualian ini pengguna terkunci total — tidak bisa ganti sandi. */
    public function test_halaman_ganti_sandi_tetap_boleh_diakses(): void
    {
        $this->actingAs($this->akunDireset())->put('/ganti-sandi', [
            'password' => 'sandibaru123',
            'password_confirmation' => 'sandibaru123',
        ])->assertRedirect('/');
    }

    /** ...dan tidak bisa keluar. */
    public function test_keluar_tetap_boleh_diakses(): void
    {
        $this->actingAs($this->akunDireset())->post('/keluar')->assertRedirect(route('masuk'));

        $this->assertGuest();
    }

    public function test_ganti_sandi_mematikan_flag_dan_menyimpan_hash_baru(): void
    {
        $user = $this->akunDireset();

        $this->actingAs($user)->put('/ganti-sandi', [
            'password' => 'sandibaru123',
            'password_confirmation' => 'sandibaru123',
        ]);

        $user->refresh();

        $this->assertFalse($user->harus_ganti_sandi);
        $this->assertTrue(Hash::check('sandibaru123', $user->password));
    }

    public function test_setelah_ganti_sandi_halaman_lain_bisa_diakses(): void
    {
        $user = $this->akunDireset();

        $this->actingAs($user)->put('/ganti-sandi', [
            'password' => 'sandibaru123',
            'password_confirmation' => 'sandibaru123',
        ]);

        $this->actingAs($user->fresh())->get('/')->assertOk();
    }

    public function test_sandi_baru_tetap_kena_aturan_minimal_delapan_karakter(): void
    {
        $user = $this->akunDireset();

        $this->actingAs($user)->put('/ganti-sandi', [
            'password' => 'pendek',
            'password_confirmation' => 'pendek',
        ])->assertSessionHasErrors('password');

        $this->assertTrue($user->fresh()->harus_ganti_sandi);
    }

    public function test_tamu_tidak_bisa_membuka_halaman_ganti_sandi(): void
    {
        $this->get('/ganti-sandi')->assertRedirect(route('masuk'));
    }
}
