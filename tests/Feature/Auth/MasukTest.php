<?php

namespace Tests\Feature\Auth;

use App\Models\Member;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class MasukTest extends TestCase
{
    use RefreshDatabase;

    private const PESAN_KREDENSIAL = 'Username atau kata sandi salah.';

    private const PESAN_PENDING = 'Akun kamu masih menunggu persetujuan pengurus.';

    private const PESAN_TIDAK_AKTIF = 'Akun kamu sedang tidak aktif. Hubungi pengurus kalau ini keliru.';

    protected function setUp(): void
    {
        parent::setUp();

        RateLimiter::clear('masuk');
    }

    private function akun(string $status = 'aktif', array $ganti = []): User
    {
        $user = User::factory()->create([
            'username' => 'fikri_r',
            'password' => 'rahasia123',
            ...$ganti,
        ]);

        Member::factory()->for($user)->create(['status' => $status]);

        return $user;
    }

    public function test_bisa_masuk_dengan_username(): void
    {
        $user = $this->akun();

        $response = $this->post('/masuk', [
            'username' => 'fikri_r',
            'password' => 'rahasia123',
        ]);

        $response->assertRedirect('/');
        $this->assertAuthenticatedAs($user);
    }

    /** A-10: username disimpan lowercase, jadi kapital apa pun harus tetap bisa masuk. */
    public function test_username_tidak_peka_huruf_besar_kecil(): void
    {
        $user = $this->akun();

        $this->post('/masuk', ['username' => 'FIKRI_R', 'password' => 'rahasia123']);

        $this->assertAuthenticatedAs($user);
    }

    /** Skenario 4. */
    public function test_sandi_salah_memberi_pesan_generik(): void
    {
        $this->akun();

        $response = $this->post('/masuk', [
            'username' => 'fikri_r',
            'password' => 'sandi-yang-salah',
        ]);

        $response->assertSessionHasErrors(['username' => self::PESAN_KREDENSIAL]);
        $this->assertGuest();
    }

    /**
     * Skenario 5: pesannya harus sama PERSIS dengan skenario 4, kalau tidak
     * penyerang bisa menebak username mana yang terdaftar (A-5).
     */
    public function test_username_tidak_terdaftar_memberi_pesan_yang_sama_persis(): void
    {
        $this->akun();

        $tidakAda = $this->post('/masuk', [
            'username' => 'tidakadaorang',
            'password' => 'sandi-yang-salah',
        ]);

        $tidakAda->assertSessionHasErrors(['username' => self::PESAN_KREDENSIAL]);
        $this->assertGuest();
    }

    /** Skenario 3. */
    public function test_akun_pending_dapat_pesan_berbeda(): void
    {
        $this->akun('pending');

        $response = $this->post('/masuk', [
            'username' => 'fikri_r',
            'password' => 'rahasia123',
        ]);

        $response->assertSessionHasErrors(['username' => self::PESAN_PENDING]);
        $this->assertGuest();
    }

    /** Status non_aktif dan alumni ditolak dengan pesan berbeda dari pending. */
    #[TestWith(['non_aktif'])]
    #[TestWith(['alumni'])]
    public function test_akun_tidak_aktif_ditolak(string $status): void
    {
        $this->akun($status);

        $response = $this->post('/masuk', [
            'username' => 'fikri_r',
            'password' => 'rahasia123',
        ]);

        $response->assertSessionHasErrors(['username' => self::PESAN_TIDAK_AKTIF]);
        $this->assertGuest();
    }

    /** Pesan tidak aktif harus beda dari pesan pending. */
    public function test_pesan_tidak_aktif_berbeda_dari_pesan_pending(): void
    {
        $this->assertNotSame(self::PESAN_PENDING, self::PESAN_TIDAK_AKTIF);
    }

    /** Akun pending dengan sandi salah tetap dapat pesan A-5, bukan A-6. */
    public function test_akun_pending_dengan_sandi_salah_tetap_pesan_kredensial(): void
    {
        $this->akun('pending');

        $response = $this->post('/masuk', [
            'username' => 'fikri_r',
            'password' => 'sandi-yang-salah',
        ]);

        $response->assertSessionHasErrors(['username' => self::PESAN_KREDENSIAL]);
    }

    /** Skenario 7. */
    public function test_percobaan_keenam_dalam_semenit_ditolak(): void
    {
        $this->akun();

        for ($i = 1; $i <= 5; $i++) {
            $this->post('/masuk', ['username' => 'fikri_r', 'password' => 'salah'])
                ->assertSessionHasErrors(['username' => self::PESAN_KREDENSIAL]);
        }

        $this->post('/masuk', ['username' => 'fikri_r', 'password' => 'salah'])
            ->assertSessionHasErrors(['username' => 'Terlalu banyak percobaan masuk. Coba lagi dalam satu menit.']);
    }

    public function test_bisa_keluar(): void
    {
        $user = $this->akun();

        $this->actingAs($user)->post('/keluar')->assertRedirect(route('masuk'));

        $this->assertGuest();
    }
}
