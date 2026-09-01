<?php

namespace Tests\Feature\Panel;

use App\Filament\Resources\Anggota\Pages\ListAnggota;
use App\Models\AuditLog;
use App\Models\Member;
use App\Models\User;
use App\Support\SandiSementara;
use Filament\Actions\Testing\TestAction;
use Filament\Notifications\Livewire\Notifications;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

/**
 * A-7: reset kata sandi anggota oleh pengurus.
 *
 * Perhatikan hak aksesnya BERBEDA dari B-2 dan B-5 yang ada di resource yang
 * sama: A-7 menyebut Guru Besar, Sekben Umum, DAN Admin.
 *
 * Aksi ini baru aman sesudah A-8 menutup panel — sandi sementara yang
 * dihasilkannya langsung tunduk pada pemaksaan ganti sandi sejak menit pertama.
 */
class AksiResetSandiTest extends TestCase
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

    private function aksi(Member $record): TestAction
    {
        return TestAction::make('resetSandi')->table($record);
    }

    // --- Siapa yang boleh (A-7 ≠ B-2) ----------------------------------------

    /** A-7 menyebut ketiganya — Admin TERMASUK, berbeda dari B-2 dan B-5. */
    #[TestWith(['is_guru_besar'])]
    #[TestWith(['is_sekben'])]
    #[TestWith(['is_admin'])]
    public function test_ketiganya_melihat_aksi_reset(string $bendera): void
    {
        $this->actingAs($this->penggunaDengan([$bendera]));
        $anggota = Member::factory()->create();

        Livewire::test(ListAnggota::class)
            ->assertActionVisible($this->aksi($anggota));
    }

    public function test_editor_tidak_melihat_aksi_reset(): void
    {
        $this->actingAs($this->penggunaDengan(['is_editor']));
        $anggota = Member::factory()->create();

        Livewire::test(ListAnggota::class)
            ->assertActionHidden($this->aksi($anggota));
    }

    /**
     * Bukti bahwa A-7 tidak disamakan dengan B-2 hanya karena berdekatan:
     * Admin melihat reset, tapi tidak melihat aksi tingkat & sabuk.
     */
    public function test_admin_melihat_reset_tapi_tidak_melihat_aksi_b2(): void
    {
        $this->actingAs($this->penggunaDengan(['is_admin']));
        $anggota = Member::factory()->create();

        Livewire::test(ListAnggota::class)
            ->assertActionVisible($this->aksi($anggota))
            ->assertActionHidden(TestAction::make('ubahTingkatSabuk')->table($anggota));
    }

    // --- Sandi sementara -----------------------------------------------------

    public function test_reset_mengganti_sandi_dan_memaksa_penggantian(): void
    {
        $this->actingAs($this->penggunaDengan(['is_admin']));

        $anggota = Member::factory()->create();
        $sandiLama = $anggota->user->password;

        $this->assertFalse($anggota->user->harus_ganti_sandi);

        Livewire::test(ListAnggota::class)
            ->callAction($this->aksi($anggota));

        $user = $anggota->user->fresh();

        $this->assertNotSame($sandiLama, $user->password);
        $this->assertTrue($user->harus_ganti_sandi);
    }

    public function test_sandi_sementara_memenuhi_a3_dan_tanpa_karakter_ambigu(): void
    {
        for ($i = 0; $i < 20; $i++) {
            $sandi = SandiSementara::buat();

            $this->assertGreaterThanOrEqual(8, strlen($sandi));
            $this->assertTrue(SandiSementara::sahih($sandi), $sandi);
            // 0/O dan 1/l/I dibuang supaya tidak salah ketik saat disalin
            // dari pesan WhatsApp.
            $this->assertDoesNotMatchRegularExpression('/[0O1lI]/', $sandi);
        }
    }

    public function test_sandi_sementara_tidak_pernah_sama(): void
    {
        $kumpulan = collect(range(1, 50))->map(fn (): string => SandiSementara::buat());

        $this->assertSame(50, $kumpulan->unique()->count());
    }

    /** Sandi yang ditampilkan benar-benar sandi yang tersimpan. */
    public function test_sandi_yang_ditampilkan_bisa_dipakai_masuk(): void
    {
        $this->actingAs($this->penggunaDengan(['is_sekben']));
        $anggota = Member::factory()->aktif()->create();

        Livewire::test(ListAnggota::class)->callAction($this->aksi($anggota));

        $sandi = $this->sandiDariNotifikasi();

        $this->assertNotNull($sandi, 'sandi sementara tidak ditemukan di notifikasi');
        $this->assertTrue(Hash::check($sandi, $anggota->user->fresh()->password));
    }

    /**
     * Notifikasi adalah satu-satunya tempat sandi itu pernah muncul. Dibaca
     * lewat komponen yang sama dengan yang dipakai Filament sendiri untuk
     * assertNotified(), bukan dengan menebak isi session.
     *
     * Baris pertama body-nya adalah sandinya; sisanya instruksi.
     */
    private function sandiDariNotifikasi(): ?string
    {
        $komponen = new Notifications;
        $komponen->mount();

        $body = $komponen->notifications
            ->map(fn ($n) => $n->getBody())
            ->filter()
            ->first();

        if ($body === null) {
            return null;
        }

        return trim(explode(chr(10), strip_tags((string) $body))[0]);
    }

    // --- Sandinya tidak boleh tersimpan di mana pun --------------------------

    /**
     * A-7: ditampilkan SEKALI. Sesudah itu tidak boleh bisa dilihat lagi —
     * termasuk di audit log, yang justru permanen.
     */
    public function test_sandi_tidak_muncul_di_audit_log(): void
    {
        $this->actingAs($this->penggunaDengan(['is_admin']));
        $anggota = Member::factory()->create();

        Livewire::test(ListAnggota::class)->callAction($this->aksi($anggota));

        foreach (AuditLog::all() as $log) {
            $isi = json_encode([$log->before, $log->after]);

            $this->assertStringNotContainsString('password', (string) $isi);
            $this->assertArrayNotHasKey('password', $log->after ?? []);
            $this->assertArrayNotHasKey('password', $log->before ?? []);
        }
    }

    /** Sandinya tidak ikut tersimpan sebagai teks biasa di kolom mana pun. */
    public function test_sandi_tersimpan_hanya_sebagai_hash(): void
    {
        $this->actingAs($this->penggunaDengan(['is_sekben']));
        $anggota = Member::factory()->create();

        Livewire::test(ListAnggota::class)->callAction($this->aksi($anggota));
        $sandi = $this->sandiDariNotifikasi();

        $user = $anggota->user->fresh();

        $this->assertNotSame($sandi, $user->password);
        $this->assertStringStartsWith('$2y$', $user->password);
    }

    // --- Audit log (B-10 lewat A-7) ------------------------------------------

    public function test_reset_tertulis_di_audit_dengan_pelaku_dan_target(): void
    {
        $pengurus = $this->penggunaDengan(['is_admin']);
        $this->actingAs($pengurus);

        $anggota = Member::factory()->create();

        Livewire::test(ListAnggota::class)->callAction($this->aksi($anggota));

        $log = AuditLog::latest('id')->first();

        $this->assertSame('diubah', $log->aksi);
        $this->assertSame(User::class, $log->subject_type);
        $this->assertSame($anggota->user_id, (int) $log->subject_id);
        $this->assertSame($pengurus->id, (int) $log->actor_id);
        $this->assertFalse($log->before['harus_ganti_sandi']);
        $this->assertTrue($log->after['harus_ganti_sandi']);
    }

    // --- A-8 langsung berlaku ------------------------------------------------

    /**
     * Inti alasan aksi ini baru aman sekarang: sandi sementara tunduk pada A-8
     * sejak menit pertama, termasuk di panel.
     */
    public function test_akun_yang_direset_dipaksa_ganti_sandi_saat_masuk(): void
    {
        $pengurus = $this->penggunaDengan(['is_admin']);
        $this->actingAs($pengurus);

        // Target juga pemegang hak akses, supaya panelnya ikut teruji.
        $target = $this->penggunaDengan(['is_sekben']);
        $anggota = Member::factory()->aktif()->create(['user_id' => $target->id]);

        Livewire::test(ListAnggota::class)->callAction($this->aksi($anggota));
        $sandi = $this->sandiDariNotifikasi();

        auth()->logout();
        session()->flush();

        // Masuk dengan sandi sementaranya.
        $this->post(route('masuk'), [
            'username' => $target->username,
            'password' => $sandi,
        ]);

        $this->assertAuthenticatedAs($target->fresh());

        // Zona anggota dan panel sama-sama tertutup sampai sandinya diganti.
        $this->get('/ganti-sandi')->assertSuccessful();
        $this->get('/admin')->assertRedirect(route('ganti-sandi.edit'));
        $this->get('/admin/anggota')->assertRedirect(route('ganti-sandi.edit'));
    }

    public function test_panel_terbuka_lagi_sesudah_sandinya_diganti(): void
    {
        $this->actingAs($this->penggunaDengan(['is_admin']));

        $target = $this->penggunaDengan(['is_sekben']);
        $anggota = Member::factory()->aktif()->create(['user_id' => $target->id]);

        Livewire::test(ListAnggota::class)->callAction($this->aksi($anggota));

        $this->actingAs($target->fresh())->put(route('ganti-sandi.update'), [
            'password' => 'sandibarusaya',
            'password_confirmation' => 'sandibarusaya',
        ]);

        $this->assertFalse($target->fresh()->harus_ganti_sandi);
        $this->actingAs($target->fresh())->get('/admin')->assertSuccessful();
    }
}
