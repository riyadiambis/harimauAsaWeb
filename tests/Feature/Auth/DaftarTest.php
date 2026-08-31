<?php

namespace Tests\Feature\Auth;

use App\Models\Member;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DaftarTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, string>
     */
    private function isian(array $ganti = []): array
    {
        return array_merge([
            'nama' => 'Rangga Saputra',
            'username' => 'ranggasap',
            'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123',
        ], $ganti);
    }

    public function test_pendaftaran_membuat_akun_berstatus_pending(): void
    {
        $response = $this->post('/daftar', $this->isian());

        $response->assertRedirect(route('daftar.selesai'));

        $user = User::where('username', 'ranggasap')->firstOrFail();

        $this->assertSame('Rangga Saputra', $user->nama);
        $this->assertSame('pending', $user->member->status);
    }

    /** A-4: pendaftar tidak boleh langsung masuk. */
    public function test_pendaftaran_tidak_auto_login(): void
    {
        $this->post('/daftar', $this->isian());

        $this->assertGuest();
    }

    /** Skenario 1. */
    public function test_username_yang_sudah_ada_ditolak(): void
    {
        Member::factory()->for(User::factory()->state(['username' => 'ranggasap']))->create();

        $response = $this->post('/daftar', $this->isian());

        $response->assertSessionHasErrors('username');
        $this->assertSame(1, User::where('username', 'ranggasap')->count());
    }

    /** Skenario 1, varian: username milik akun yang sudah di-soft-delete tetap terpakai. */
    public function test_username_milik_akun_terhapus_tetap_ditolak(): void
    {
        User::factory()->create(['username' => 'ranggasap'])->delete();

        $response = $this->post('/daftar', $this->isian());

        $response->assertSessionHasErrors('username');
    }

    /** Skenario 2, bagian "dinormalkan". */
    public function test_username_huruf_besar_dinormalkan(): void
    {
        $this->post('/daftar', $this->isian(['username' => 'RanggaSap']));

        // Diperiksa sebagai string PHP, bukan lewat assertDatabaseMissing:
        // collation MySQL tidak peka huruf besar-kecil, jadi mencari "RanggaSap"
        // tetap menemukan baris "ranggasap" dan assertion itu tidak membuktikan apa pun.
        $this->assertSame('ranggasap', User::sole()->username);
    }

    /** Skenario 2, bagian "ditolak". */
    public function test_username_berisi_spasi_atau_karakter_lain_ditolak(): void
    {
        foreach (['rangga sap', 'rangga.sap', 'rangga-sap', 'ran', str_repeat('a', 21)] as $username) {
            $this->post('/daftar', $this->isian(['username' => $username]))
                ->assertSessionHasErrors('username');
        }

        $this->assertSame(0, User::count());
    }

    /** A-3. */
    public function test_kata_sandi_minimal_delapan_karakter_dan_harus_cocok(): void
    {
        $this->post('/daftar', $this->isian([
            'password' => 'pendek',
            'password_confirmation' => 'pendek',
        ]))->assertSessionHasErrors('password');

        $this->post('/daftar', $this->isian([
            'password_confirmation' => 'bedasendiri',
        ]))->assertSessionHasErrors('password');

        $this->assertSame(0, User::count());
    }

    /** Hak akses tidak boleh bisa diberikan sendiri lewat form pendaftaran (B-6). */
    public function test_hak_akses_tidak_bisa_ditembus_lewat_form(): void
    {
        $this->post('/daftar', $this->isian([
            'is_admin' => '1',
            'is_guru_besar' => '1',
            'harus_ganti_sandi' => '1',
        ]));

        $user = User::where('username', 'ranggasap')->firstOrFail();

        $this->assertFalse($user->is_admin);
        $this->assertFalse($user->is_guru_besar);
        $this->assertFalse($user->harus_ganti_sandi);
    }
}
