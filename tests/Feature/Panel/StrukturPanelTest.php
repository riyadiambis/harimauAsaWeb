<?php

namespace Tests\Feature\Panel;

use App\Exceptions\SiklusAtasanException;
use App\Filament\Resources\Jabatan\JabatanResource;
use App\Filament\Resources\Jabatan\Pages\ListJabatan;
use App\Filament\Resources\Periode\Pages\ListPeriode;
use App\Filament\Resources\Periode\PeriodeResource;
use App\Models\AuditLog;
use App\Models\Jabatan;
use App\Models\PeriodeKepengurusan;
use App\Models\Ranting;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

/**
 * Panel struktur: periode kepengurusan (B-8, B-9) dan jabatan (B-3, B-19).
 * Hak kelolanya B-4 untuk keduanya.
 */
class StrukturPanelTest extends TestCase
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

    // --- B-4: siapa yang boleh ------------------------------------------------

    #[TestWith(['is_guru_besar', true])]
    #[TestWith(['is_sekben', true])]
    #[TestWith(['is_admin', false])]
    #[TestWith(['is_editor', false])]
    public function test_menu_struktur_hanya_untuk_yang_berhak(string $bendera, bool $terlihat): void
    {
        $this->actingAs($this->penggunaDengan([$bendera]));

        $this->assertSame($terlihat, PeriodeResource::canViewAny(), "periode / {$bendera}");
        $this->assertSame($terlihat, JabatanResource::canViewAny(), "jabatan / {$bendera}");
    }

    public function test_admin_ditolak_di_halaman_periode_dan_jabatan(): void
    {
        $admin = $this->penggunaDengan(['is_admin']);

        $this->actingAs($admin)->get(PeriodeResource::getUrl('index'))->assertForbidden();
        $this->actingAs($admin)->get(JabatanResource::getUrl('index'))->assertForbidden();
    }

    public function test_guru_besar_bisa_membuka_keduanya(): void
    {
        $gb = $this->penggunaDengan(['is_guru_besar']);
        PeriodeKepengurusan::factory()->create();

        $this->actingAs($gb)->get(PeriodeResource::getUrl('index'))->assertSuccessful();
        $this->actingAs($gb)->get(JabatanResource::getUrl('index'))->assertSuccessful();
    }

    // --- B-8: satu periode aktif ---------------------------------------------

    /** Skenario 6: menandai periode baru aktif menonaktifkan yang lama. */
    public function test_menandai_periode_aktif_menonaktifkan_yang_lama(): void
    {
        $this->actingAs($this->penggunaDengan(['is_sekben']));

        $lama = PeriodeKepengurusan::factory()->create(['aktif' => true]);
        $baru = PeriodeKepengurusan::factory()->create(['aktif' => false]);

        Livewire::test(ListPeriode::class)
            ->callAction(TestAction::make('edit')->table($baru), [
                'nama' => $baru->nama,
                'tahun_mulai' => $baru->tahun_mulai,
                'tahun_selesai' => $baru->tahun_selesai,
                'aktif' => true,
            ]);

        $this->assertTrue($baru->refresh()->aktif);
        $this->assertFalse($lama->refresh()->aktif);
        // B-9: yang lama tetap ada, hanya dinonaktifkan.
        $this->assertModelExists($lama);
    }

    public function test_hanya_satu_periode_aktif_setelah_beberapa_kali_diaktifkan(): void
    {
        $periode = PeriodeKepengurusan::factory()->count(4)->create(['aktif' => false]);

        foreach ($periode as $p) {
            $p->update(['aktif' => true]);
        }

        $this->assertSame(1, PeriodeKepengurusan::where('aktif', true)->count());
    }

    // --- B-9: penolakan hapus yang terbaca -----------------------------------

    public function test_panel_menolak_hapus_periode_dengan_notifikasi(): void
    {
        $this->actingAs($this->penggunaDengan(['is_guru_besar']));

        $periode = PeriodeKepengurusan::factory()->create(['nama' => 'Kepengurusan 2015-2016']);
        Jabatan::factory()->count(2)->create(['periode_id' => $periode->id]);

        Livewire::test(ListPeriode::class)
            ->callAction(TestAction::make('delete')->table($periode))
            ->assertNotified('Periode ini tidak bisa dihapus');

        $this->assertModelExists($periode);
    }

    public function test_panel_meloloskan_hapus_periode_kosong(): void
    {
        $this->actingAs($this->penggunaDengan(['is_sekben']));
        $periode = PeriodeKepengurusan::factory()->create();

        Livewire::test(ListPeriode::class)
            ->callAction(TestAction::make('delete')->table($periode));

        $this->assertModelMissing($periode);
    }

    // --- B-3: nama_jabatan teks bebas ----------------------------------------

    /** Skenario 8: teks apa pun diterima, tanpa validasi terhadap daftar. */
    #[TestWith(['Humas Ranting Melak'])]
    #[TestWith(['Koordinator Latihan Sore'])]
    #[TestWith(['Sekretaris II'])]
    #[TestWith(['Penasihat & Pembina'])]
    public function test_nama_jabatan_menerima_teks_bebas(string $nama): void
    {
        $this->actingAs($this->penggunaDengan(['is_guru_besar']));

        $periode = PeriodeKepengurusan::factory()->create();
        $user = User::factory()->create();

        Livewire::test(ListJabatan::class)
            ->callAction('create', [
                'periode_id' => $periode->id,
                'user_id' => $user->id,
                'nama_jabatan' => $nama,
                'parent_id' => null,
                'ranting_id' => null,
                'urutan' => 0,
            ])
            ->assertHasNoActionErrors();

        $this->assertDatabaseHas('jabatan', ['nama_jabatan' => $nama]);
    }

    // --- B-19: siklus atasan --------------------------------------------------

    public function test_jabatan_tidak_bisa_jadi_atasan_dirinya_sendiri(): void
    {
        $jabatan = Jabatan::factory()->create();

        $this->expectException(SiklusAtasanException::class);
        $this->expectExceptionMessage('atasan dirinya sendiri');

        $jabatan->update(['parent_id' => $jabatan->id]);
    }

    public function test_siklus_dua_jabatan_ditolak(): void
    {
        $periode = PeriodeKepengurusan::factory()->create();
        $a = Jabatan::factory()->create(['periode_id' => $periode->id, 'nama_jabatan' => 'A']);
        $b = Jabatan::factory()->create(['periode_id' => $periode->id, 'nama_jabatan' => 'B', 'parent_id' => $a->id]);

        $this->expectException(SiklusAtasanException::class);
        $this->expectExceptionMessage('sudah ada di rantai atasannya');

        $a->update(['parent_id' => $b->id]);
    }

    /** Rantai panjang juga ditelusuri, bukan cuma tetangga langsung. */
    public function test_siklus_lewat_tiga_jabatan_ditolak(): void
    {
        $periode = PeriodeKepengurusan::factory()->create();
        $a = Jabatan::factory()->create(['periode_id' => $periode->id]);
        $b = Jabatan::factory()->create(['periode_id' => $periode->id, 'parent_id' => $a->id]);
        $c = Jabatan::factory()->create(['periode_id' => $periode->id, 'parent_id' => $b->id]);

        $this->expectException(SiklusAtasanException::class);

        $a->update(['parent_id' => $c->id]);
    }

    /** Skenario 7: bagan tiga tingkat yang sah tetap boleh. */
    public function test_bagan_tiga_tingkat_yang_sah_diterima(): void
    {
        $periode = PeriodeKepengurusan::factory()->create();
        $akar = Jabatan::factory()->create(['periode_id' => $periode->id]);
        $tengah = Jabatan::factory()->create(['periode_id' => $periode->id, 'parent_id' => $akar->id]);
        $daun = Jabatan::factory()->create(['periode_id' => $periode->id, 'parent_id' => $tengah->id]);

        $this->assertSame($akar->id, $daun->parent->parent->id);
        $this->assertCount(3, $daun->rantaiAtasan());
    }

    public function test_pilihan_atasan_tidak_memuat_dirinya_maupun_bawahannya(): void
    {
        $periode = PeriodeKepengurusan::factory()->create();
        $akar = Jabatan::factory()->create(['periode_id' => $periode->id]);
        $anak = Jabatan::factory()->create(['periode_id' => $periode->id, 'parent_id' => $akar->id]);
        $cucu = Jabatan::factory()->create(['periode_id' => $periode->id, 'parent_id' => $anak->id]);
        $lain = Jabatan::factory()->create(['periode_id' => $periode->id]);

        $calon = $akar->idCalonAtasan();

        $this->assertNotContains($akar->id, $calon);
        $this->assertNotContains($anak->id, $calon);
        $this->assertNotContains($cucu->id, $calon);
        $this->assertContains($lain->id, $calon);
    }

    /** Atasan wajib satu periode. */
    public function test_pilihan_atasan_hanya_dari_periode_yang_sama(): void
    {
        $periodeA = PeriodeKepengurusan::factory()->create();
        $periodeB = PeriodeKepengurusan::factory()->create();

        $jabatan = Jabatan::factory()->create(['periode_id' => $periodeA->id]);
        $sePeriode = Jabatan::factory()->create(['periode_id' => $periodeA->id]);
        $bedaPeriode = Jabatan::factory()->create(['periode_id' => $periodeB->id]);

        $calon = $jabatan->idCalonAtasan();

        $this->assertContains($sePeriode->id, $calon);
        $this->assertNotContains($bedaPeriode->id, $calon);
    }

    // --- Aturan hapus induk ---------------------------------------------------

    /** Bawahan naik jadi akar, tidak ikut terhapus — dijaga foreign key. */
    public function test_menghapus_jabatan_induk_menaikkan_bawahannya_jadi_akar(): void
    {
        $induk = Jabatan::factory()->create();
        $anak = Jabatan::factory()->create([
            'periode_id' => $induk->periode_id,
            'parent_id' => $induk->id,
        ]);

        $induk->delete();

        $this->assertModelExists($anak);
        $this->assertNull($anak->fresh()->parent_id);
    }

    public function test_menghapus_ranting_tidak_menghapus_jabatannya(): void
    {
        $ranting = Ranting::factory()->create();
        $jabatan = Jabatan::factory()->create(['ranting_id' => $ranting->id]);

        $ranting->delete();

        $this->assertModelExists($jabatan);
        $this->assertNull($jabatan->fresh()->ranting_id);
    }

    // --- Audit log (B-10) -----------------------------------------------------

    public function test_perubahan_periode_tertulis_di_audit(): void
    {
        $pengurus = $this->penggunaDengan(['is_sekben']);
        $this->actingAs($pengurus);

        $periode = PeriodeKepengurusan::factory()->create(['nama' => 'Kepengurusan 2020-2021']);
        $periode->update(['nama' => 'Kepengurusan 2020-2022']);

        $log = AuditLog::latest('id')->first();

        $this->assertSame('diubah', $log->aksi);
        $this->assertSame(PeriodeKepengurusan::class, $log->subject_type);
        $this->assertSame($pengurus->id, (int) $log->actor_id);
        $this->assertSame('Kepengurusan 2020-2021', $log->before['nama']);
        $this->assertSame('Kepengurusan 2020-2022', $log->after['nama']);
    }

    public function test_pembuatan_dan_penghapusan_jabatan_tertulis_di_audit(): void
    {
        $pengurus = $this->penggunaDengan(['is_guru_besar']);
        $this->actingAs($pengurus);

        $jabatan = Jabatan::factory()->create(['nama_jabatan' => 'Humas Ranting Melak']);

        $dibuat = AuditLog::latest('id')->first();
        $this->assertSame('dibuat', $dibuat->aksi);
        $this->assertSame('Humas Ranting Melak', $dibuat->after['nama_jabatan']);

        $jabatan->delete();

        $dihapus = AuditLog::latest('id')->first();
        $this->assertSame('dihapus', $dihapus->aksi);
        $this->assertSame('Humas Ranting Melak', $dihapus->before['nama_jabatan']);
    }

    // --- Halaman yang BENAR-BENAR dirender ------------------------------------

    /**
     * Regresi kelas ActionGroup: TestAction mengikat record secara eksplisit,
     * jadi ia tetap hijau walau kontrol per baris salah di halaman sungguhan.
     * Diperiksa lewat HTML yang keluar.
     */
    public function test_kontrol_per_baris_benar_di_halaman_periode(): void
    {
        $gb = $this->penggunaDengan(['is_guru_besar']);

        $render = function (): string {
            return $this->actingAs(User::where('is_guru_besar', true)->first())
                ->get(PeriodeResource::getUrl('index'))
                ->getContent();
        };

        // Diukur relatif, bukan dipatok angka: Filament merender beberapa
        // potong markup per aksi, dan jumlah pastinya bukan urusan tes ini.
        // Yang dijaga: kontrolnya tumbuh sebanding dengan jumlah baris.
        $satu = PeriodeKepengurusan::factory()->create(['aktif' => false]);
        $perBaris = substr_count($render(), "mountAction('delete'");

        $this->assertGreaterThan(0, $perBaris, 'baris tunggal harus punya kontrol hapus');

        PeriodeKepengurusan::factory()->count(2)->create(['aktif' => false]);
        $html = $render();

        $this->assertSame(3 * $perBaris, substr_count($html, "mountAction('delete'"));

        // EditAction merender tautan ke halaman ubah, bukan aksi yang di-mount.
        foreach (PeriodeKepengurusan::all() as $p) {
            $this->assertStringContainsString(
                PeriodeResource::getUrl('edit', ['record' => $p]),
                $html,
                "tautan ubah periode #{$p->id}",
            );
        }

        $this->assertNotNull($satu);
    }

    public function test_halaman_jabatan_menampilkan_atasan_per_baris(): void
    {
        $gb = $this->penggunaDengan(['is_guru_besar']);

        $periode = PeriodeKepengurusan::factory()->create();
        $akar = Jabatan::factory()->create([
            'periode_id' => $periode->id,
            'nama_jabatan' => 'Guru Besar',
        ]);
        Jabatan::factory()->create([
            'periode_id' => $periode->id,
            'nama_jabatan' => 'Ketua Wilayah Kutai Barat',
            'parent_id' => $akar->id,
        ]);

        $html = $this->actingAs($gb)->get(JabatanResource::getUrl('index'))->getContent();

        $this->assertStringContainsString('Ketua Wilayah Kutai Barat', $html);
        // Baris akar menampilkan penanda puncak, bukan sel kosong.
        $this->assertStringContainsString('puncak bagan', $html);
    }

    /** Admin tidak boleh menemukan satu pun kontrol pengubah struktur. */
    public function test_halaman_struktur_tidak_terender_untuk_admin(): void
    {
        $admin = $this->penggunaDengan(['is_admin']);
        PeriodeKepengurusan::factory()->create();

        $this->actingAs($admin)->get(PeriodeResource::getUrl('index'))->assertForbidden();
        $this->actingAs($admin)->get(JabatanResource::getUrl('create'))->assertForbidden();
    }
}
