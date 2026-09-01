<?php

namespace Tests\Feature\Panel;

use App\Filament\Resources\Anggota\Pages\ListAnggota;
use App\Models\AuditLog;
use App\Models\Member;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

/**
 * B-2: mengubah tingkat keanggotaan, sabuk, dan no_warga lewat panel.
 *
 * Termasuk dua akibat yang ditanggung model, bukan resource:
 *   B-7  naik ke warga mengisi tanggal_naik_warga
 *   B-13 turun ke anggota mengosongkan no_warga dan tanggal itu
 */
class AksiTingkatSabukTest extends TestCase
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

    private function aksi(string $nama, Member $record): TestAction
    {
        return TestAction::make($nama)->table($record);
    }

    // --- B-2: siapa yang melihat aksinya -------------------------------------

    #[TestWith(['is_guru_besar'])]
    #[TestWith(['is_sekben'])]
    public function test_guru_besar_dan_sekben_melihat_aksi_tingkat_sabuk(string $bendera): void
    {
        $this->actingAs($this->penggunaDengan([$bendera]));
        $anggota = Member::factory()->create();

        Livewire::test(ListAnggota::class)
            ->assertActionVisible($this->aksi('ubahTingkatSabuk', $anggota));
    }

    /** Skenario uji 3: Admin tanpa Guru Besar/Sekben tidak boleh ubah sabuk. */
    public function test_admin_tidak_melihat_aksi_tingkat_sabuk(): void
    {
        $this->actingAs($this->penggunaDengan(['is_admin']));
        $anggota = Member::factory()->create();

        Livewire::test(ListAnggota::class)
            ->assertActionHidden($this->aksi('ubahTingkatSabuk', $anggota));
    }

    public function test_editor_tidak_melihat_aksi_tingkat_sabuk(): void
    {
        $this->actingAs($this->penggunaDengan(['is_editor']));
        $anggota = Member::factory()->create();

        Livewire::test(ListAnggota::class)
            ->assertActionHidden($this->aksi('ubahTingkatSabuk', $anggota));
    }

    /** B-13: nomor warga hanya berlaku untuk tingkat warga. */
    public function test_aksi_no_warga_hanya_tampil_pada_warga(): void
    {
        $this->actingAs($this->penggunaDengan(['is_sekben']));

        $warga = Member::factory()->warga()->create();
        $anggota = Member::factory()->create(['tingkat_keanggotaan' => 'anggota']);

        Livewire::test(ListAnggota::class)
            ->assertActionVisible($this->aksi('isiNoWarga', $warga))
            ->assertActionHidden($this->aksi('isiNoWarga', $anggota));
    }

    public function test_admin_tidak_melihat_aksi_no_warga(): void
    {
        $this->actingAs($this->penggunaDengan(['is_admin']));
        $warga = Member::factory()->warga()->create();

        Livewire::test(ListAnggota::class)
            ->assertActionHidden($this->aksi('isiNoWarga', $warga));
    }

    // --- Mutator tingkatan_urutan --------------------------------------------

    /**
     * Skenario uji 1: jalur UI wajib lewat mutator di model, bukan menghitung
     * urutannya sendiri di resource.
     */
    #[TestWith(['hitam_polos', 1])]
    #[TestWith(['kuning', 2])]
    #[TestWith(['oren', 3])]
    #[TestWith(['merah_warga_1', 4])]
    #[TestWith(['merah_warga_2', 5])]
    #[TestWith(['putih_warga_3', 6])]
    public function test_ubah_sabuk_mengisi_tingkatan_urutan(string $sabuk, int $urutan): void
    {
        $this->actingAs($this->penggunaDengan(['is_guru_besar']));
        $anggota = Member::factory()->create(['tingkatan' => 'hitam_polos']);

        Livewire::test(ListAnggota::class)
            ->callAction($this->aksi('ubahTingkatSabuk', $anggota), [
                'tingkat_keanggotaan' => $anggota->tingkat_keanggotaan,
                'tingkatan' => $sabuk,
            ]);

        $anggota->refresh();

        $this->assertSame($sabuk, $anggota->tingkatan);
        $this->assertSame($urutan, $anggota->tingkatan_urutan);
    }

    // --- B-7: naik ke warga --------------------------------------------------

    public function test_naik_warga_mengisi_tanggal_naik_warga(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-02 10:00:00', 'Asia/Makassar'));

        $this->actingAs($this->penggunaDengan(['is_sekben']));
        $anggota = Member::factory()->create([
            'tingkat_keanggotaan' => 'anggota',
            'tanggal_naik_warga' => null,
        ]);

        Livewire::test(ListAnggota::class)
            ->callAction($this->aksi('ubahTingkatSabuk', $anggota), [
                'tingkat_keanggotaan' => 'warga',
                'tingkatan' => 'merah_warga_1',
            ]);

        $anggota->refresh();

        $this->assertSame('warga', $anggota->tingkat_keanggotaan);
        $this->assertSame('2026-09-02', $anggota->tanggal_naik_warga->toDateString());

        Carbon::setTestNow();
    }

    /** Yang sudah warga dan tanggalnya terisi tidak ditimpa. */
    public function test_tanggal_naik_warga_yang_sudah_ada_tidak_ditimpa(): void
    {
        $this->actingAs($this->penggunaDengan(['is_sekben']));
        $warga = Member::factory()->warga()->create(['tanggal_naik_warga' => '2024-02-01']);

        Livewire::test(ListAnggota::class)
            ->callAction($this->aksi('ubahTingkatSabuk', $warga), [
                'tingkat_keanggotaan' => 'warga',
                'tingkatan' => 'merah_warga_2',
            ]);

        $this->assertSame('2024-02-01', $warga->refresh()->tanggal_naik_warga->toDateString());
    }

    // --- Turun tingkat -------------------------------------------------------

    /**
     * Turun ke `anggota` mengosongkan no_warga (B-13: hanya berlaku untuk
     * warga) dan tanggal_naik_warga — tanggal basi berbahaya, karena fitur 03
     * akan membacanya kalau orangnya naik warga lagi nanti.
     */
    public function test_turun_ke_anggota_mengosongkan_no_warga_dan_tanggal(): void
    {
        $this->actingAs($this->penggunaDengan(['is_guru_besar']));

        $warga = Member::factory()->warga()->create([
            'no_warga' => '12345678',
            'tanggal_naik_warga' => '2024-02-01',
        ]);

        Livewire::test(ListAnggota::class)
            ->callAction($this->aksi('ubahTingkatSabuk', $warga), [
                'tingkat_keanggotaan' => 'anggota',
                'tingkatan' => 'oren',
            ]);

        $warga->refresh();

        $this->assertSame('anggota', $warga->tingkat_keanggotaan);
        $this->assertNull($warga->no_warga);
        $this->assertNull($warga->tanggal_naik_warga);
    }

    /** Naik lagi sesudah turun mendapat tanggal BARU, bukan yang lama. */
    public function test_naik_lagi_sesudah_turun_memakai_tanggal_baru(): void
    {
        $this->actingAs($this->penggunaDengan(['is_sekben']));

        $anggota = Member::factory()->warga()->create(['tanggal_naik_warga' => '2020-01-01']);

        $komponen = Livewire::test(ListAnggota::class);

        $komponen->callAction($this->aksi('ubahTingkatSabuk', $anggota), [
            'tingkat_keanggotaan' => 'anggota',
            'tingkatan' => 'oren',
        ]);

        Carbon::setTestNow(Carbon::parse('2026-09-02 10:00:00', 'Asia/Makassar'));

        $komponen->callAction($this->aksi('ubahTingkatSabuk', $anggota->refresh()), [
            'tingkat_keanggotaan' => 'warga',
            'tingkatan' => 'merah_warga_1',
        ]);

        $this->assertSame('2026-09-02', $anggota->refresh()->tanggal_naik_warga->toDateString());

        Carbon::setTestNow();
    }

    /** NIA tidak ikut berubah saat tingkat berubah (B-12). */
    public function test_nia_tidak_berubah_saat_tingkat_berubah(): void
    {
        $this->actingAs($this->penggunaDengan(['is_sekben']));
        $anggota = Member::factory()->create(['status' => 'aktif', 'nia' => '2022-0005']);

        Livewire::test(ListAnggota::class)
            ->callAction($this->aksi('ubahTingkatSabuk', $anggota), [
                'tingkat_keanggotaan' => 'warga',
                'tingkatan' => 'merah_warga_1',
            ]);

        $this->assertSame('2022-0005', $anggota->refresh()->nia);
    }

    // --- B-13: no_warga ------------------------------------------------------

    public function test_no_warga_delapan_digit_diterima(): void
    {
        $this->actingAs($this->penggunaDengan(['is_sekben']));
        $warga = Member::factory()->warga()->create(['no_warga' => null]);

        Livewire::test(ListAnggota::class)
            ->callAction($this->aksi('isiNoWarga', $warga), ['no_warga' => '87654321']);

        $this->assertSame('87654321', $warga->refresh()->no_warga);
    }

    #[TestWith(['1234567'])]
    #[TestWith(['123456789'])]
    #[TestWith(['1234 678'])]
    #[TestWith(['1234567a'])]
    public function test_no_warga_selain_delapan_digit_ditolak(string $nilai): void
    {
        $this->actingAs($this->penggunaDengan(['is_sekben']));
        $warga = Member::factory()->warga()->create(['no_warga' => null]);

        Livewire::test(ListAnggota::class)
            ->callAction($this->aksi('isiNoWarga', $warga), ['no_warga' => $nilai])
            ->assertHasActionErrors(['no_warga']);

        $this->assertNull($warga->refresh()->no_warga);
    }

    public function test_no_warga_milik_orang_lain_ditolak(): void
    {
        $this->actingAs($this->penggunaDengan(['is_sekben']));

        Member::factory()->warga()->create(['no_warga' => '11112222']);
        $warga = Member::factory()->warga()->create(['no_warga' => null]);

        Livewire::test(ListAnggota::class)
            ->callAction($this->aksi('isiNoWarga', $warga), ['no_warga' => '11112222'])
            ->assertHasActionErrors(['no_warga']);

        $this->assertNull($warga->refresh()->no_warga);
    }

    /** Nomornya sendiri tidak dianggap bentrok saat disunting ulang. */
    public function test_nomor_sendiri_boleh_disimpan_ulang(): void
    {
        $this->actingAs($this->penggunaDengan(['is_sekben']));
        $warga = Member::factory()->warga()->create(['no_warga' => '55556666']);

        Livewire::test(ListAnggota::class)
            ->callAction($this->aksi('isiNoWarga', $warga), ['no_warga' => '55556666'])
            ->assertHasNoActionErrors();

        $this->assertSame('55556666', $warga->refresh()->no_warga);
    }

    // --- Audit log (B-10) ----------------------------------------------------

    public function test_perubahan_tingkat_dan_sabuk_tertulis_di_audit(): void
    {
        $pengurus = $this->penggunaDengan(['is_guru_besar']);
        $this->actingAs($pengurus);

        $anggota = Member::factory()->create([
            'tingkat_keanggotaan' => 'anggota',
            'tingkatan' => 'kuning',
        ]);

        Livewire::test(ListAnggota::class)
            ->callAction($this->aksi('ubahTingkatSabuk', $anggota), [
                'tingkat_keanggotaan' => 'warga',
                'tingkatan' => 'merah_warga_1',
            ]);

        $log = AuditLog::latest('id')->first();

        $this->assertSame('diubah', $log->aksi);
        $this->assertSame(Member::class, $log->subject_type);
        $this->assertSame($pengurus->id, (int) $log->actor_id);
        $this->assertSame('anggota', $log->before['tingkat_keanggotaan']);
        $this->assertSame('kuning', $log->before['tingkatan']);
        $this->assertSame('warga', $log->after['tingkat_keanggotaan']);
        $this->assertSame('merah_warga_1', $log->after['tingkatan']);
    }

    public function test_perubahan_no_warga_tertulis_di_audit(): void
    {
        $pengurus = $this->penggunaDengan(['is_sekben']);
        $this->actingAs($pengurus);

        $warga = Member::factory()->warga()->create(['no_warga' => null]);

        Livewire::test(ListAnggota::class)
            ->callAction($this->aksi('isiNoWarga', $warga), ['no_warga' => '99998888']);

        $log = AuditLog::latest('id')->first();

        $this->assertSame($pengurus->id, (int) $log->actor_id);
        $this->assertNull($log->before['no_warga']);
        $this->assertSame('99998888', $log->after['no_warga']);
    }
}
