<?php

namespace Tests\Feature\Panel;

use App\Exceptions\HapusIndukException;
use App\Filament\Resources\Rantings\RantingResource;
use App\Filament\Resources\Wilayahs\Pages\ListWilayahs;
use App\Filament\Resources\Wilayahs\WilayahResource;
use App\Models\AuditLog;
use App\Models\Member;
use App\Models\Ranting;
use App\Models\User;
use App\Models\Wilayah;
use Filament\Actions\Testing\TestAction;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

/**
 * B-16: wilayah dan ranting dikelola Guru Besar dan Sekben Umum saja, plus
 * aturan hapus induk dan audit log (B-10).
 */
class WilayahRantingTest extends TestCase
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

    // --- B-16: siapa yang boleh mengelola ------------------------------------

    #[TestWith(['is_guru_besar'])]
    #[TestWith(['is_sekben'])]
    public function test_guru_besar_dan_sekben_boleh_mengelola(string $bendera): void
    {
        $pengurus = $this->penggunaDengan([$bendera]);
        $wilayah = Wilayah::factory()->create();
        $ranting = Ranting::factory()->create();

        $this->assertTrue($pengurus->can('viewAny', Wilayah::class), $bendera);
        $this->assertTrue($pengurus->can('create', Wilayah::class), $bendera);
        $this->assertTrue($pengurus->can('update', $wilayah), $bendera);
        $this->assertTrue($pengurus->can('viewAny', Ranting::class), $bendera);
        $this->assertTrue($pengurus->can('update', $ranting), $bendera);
    }

    /** B-16 mengecualikan Admin, sama seperti B-2 dan B-4. */
    public function test_admin_tidak_boleh_mengelola(): void
    {
        $admin = $this->penggunaDengan(['is_admin']);

        $this->assertFalse($admin->can('viewAny', Wilayah::class));
        $this->assertFalse($admin->can('create', Wilayah::class));
        $this->assertFalse($admin->can('viewAny', Ranting::class));
    }

    /** Editor lolos gerbang panel B-15, tapi berhenti di sini. */
    public function test_editor_masuk_panel_tapi_tidak_boleh_mengelola(): void
    {
        $editor = $this->penggunaDengan(['is_editor']);

        $this->assertTrue($editor->punyaHakAkses(), 'editor harus lolos B-15');
        $this->assertFalse($editor->can('viewAny', Wilayah::class));
        $this->assertFalse($editor->can('viewAny', Ranting::class));
    }

    public function test_admin_yang_juga_sekben_boleh(): void
    {
        $rangkap = $this->penggunaDengan(['is_admin', 'is_sekben']);

        $this->assertTrue($rangkap->can('viewAny', Wilayah::class));
    }

    // --- Menu panel ----------------------------------------------------------

    #[TestWith(['is_guru_besar', true])]
    #[TestWith(['is_sekben', true])]
    #[TestWith(['is_admin', false])]
    #[TestWith(['is_editor', false])]
    public function test_menu_hanya_muncul_untuk_yang_berhak(string $bendera, bool $terlihat): void
    {
        $this->actingAs($this->penggunaDengan([$bendera]));

        $this->assertSame($terlihat, WilayahResource::canViewAny(), "wilayah / {$bendera}");
        $this->assertSame($terlihat, RantingResource::canViewAny(), "ranting / {$bendera}");
    }

    public function test_yang_tidak_berhak_ditolak_di_halaman_daftar(): void
    {
        $editor = $this->penggunaDengan(['is_editor']);

        $this->actingAs($editor)
            ->get(WilayahResource::getUrl('index'))
            ->assertForbidden();
    }

    public function test_yang_berhak_bisa_membuka_halaman_daftar(): void
    {
        $sekben = $this->penggunaDengan(['is_sekben']);
        Wilayah::factory()->create(['nama' => 'Kutai Barat']);

        $this->actingAs($sekben)
            ->get(WilayahResource::getUrl('index'))
            ->assertSuccessful();
    }

    // --- Aturan hapus induk --------------------------------------------------

    public function test_wilayah_yang_masih_punya_ranting_tidak_bisa_dihapus(): void
    {
        $wilayah = Wilayah::factory()->create(['nama' => 'Kutai Barat']);
        Ranting::factory()->create(['wilayah_id' => $wilayah->id]);

        $this->expectException(HapusIndukException::class);
        $this->expectExceptionMessage('masih punya 1 ranting');

        $wilayah->delete();
    }

    /** Pesannya harus terbaca manusia, bukan SQLSTATE. */
    public function test_pesan_penolakan_terbaca_bukan_galat_sql(): void
    {
        $wilayah = Wilayah::factory()->create(['nama' => 'Samarinda']);
        Ranting::factory()->count(3)->create(['wilayah_id' => $wilayah->id]);

        try {
            $wilayah->delete();
            $this->fail('penghapusan seharusnya ditolak');
        } catch (HapusIndukException $e) {
            $this->assertStringContainsString('Samarinda', $e->getMessage());
            $this->assertStringContainsString('3 ranting', $e->getMessage());
            $this->assertStringNotContainsString('SQLSTATE', $e->getMessage());
            $this->assertStringNotContainsString('foreign key', $e->getMessage());
        }

        $this->assertModelExists($wilayah);
    }

    public function test_wilayah_kosong_boleh_dihapus(): void
    {
        $wilayah = Wilayah::factory()->create();

        $wilayah->delete();

        $this->assertModelMissing($wilayah);
    }

    public function test_wilayah_bisa_dihapus_sesudah_rantingnya_dipindah(): void
    {
        $lama = Wilayah::factory()->create();
        $baru = Wilayah::factory()->create();
        $ranting = Ranting::factory()->create(['wilayah_id' => $lama->id]);

        $ranting->update(['wilayah_id' => $baru->id]);
        $lama->delete();

        $this->assertModelMissing($lama);
    }

    /** Database tetap jaring terakhir untuk jalur yang tidak lewat Eloquent. */
    public function test_foreign_key_tetap_menolak_di_level_database(): void
    {
        $wilayah = Wilayah::factory()->create();
        Ranting::factory()->create(['wilayah_id' => $wilayah->id]);

        $this->expectException(QueryException::class);

        DB::table('wilayah')->where('id', $wilayah->id)->delete();
    }

    /** Ranting boleh dihapus; anggotanya tetap ada dengan ranting_id null. */
    public function test_ranting_boleh_dihapus_dan_anggotanya_tetap_ada(): void
    {
        $ranting = Ranting::factory()->create();
        $member = Member::factory()->create(['ranting_id' => $ranting->id]);

        $ranting->delete();

        $this->assertModelMissing($ranting);
        $this->assertModelExists($member);
        $this->assertNull($member->fresh()->ranting_id);
    }

    // --- Audit log (B-10 lewat B-16) -----------------------------------------

    public function test_pembuatan_wilayah_tercatat(): void
    {
        $pengurus = $this->penggunaDengan(['is_sekben']);
        $this->actingAs($pengurus);

        $wilayah = Wilayah::create(['nama' => 'Berau', 'urutan' => 3]);

        $log = AuditLog::latest('id')->first();

        $this->assertSame('dibuat', $log->aksi);
        $this->assertSame(Wilayah::class, $log->subject_type);
        $this->assertSame($wilayah->id, (int) $log->subject_id);
        $this->assertSame($pengurus->id, (int) $log->actor_id);
        $this->assertSame(['nama' => 'Berau', 'urutan' => 3], $log->after);
    }

    public function test_penyuntingan_wilayah_tercatat(): void
    {
        $this->actingAs($this->penggunaDengan(['is_guru_besar']));
        $wilayah = Wilayah::factory()->create(['nama' => 'Kutai Barat']);

        $wilayah->update(['nama' => 'Kutai Barat Raya']);

        $log = AuditLog::latest('id')->first();

        $this->assertSame('diubah', $log->aksi);
        $this->assertSame(['nama' => 'Kutai Barat'], $log->before);
        $this->assertSame(['nama' => 'Kutai Barat Raya'], $log->after);
    }

    public function test_penghapusan_ranting_tercatat(): void
    {
        $this->actingAs($this->penggunaDengan(['is_sekben']));
        $ranting = Ranting::factory()->create(['nama' => 'Melak Ulu']);

        $ranting->delete();

        $log = AuditLog::latest('id')->first();

        $this->assertSame('dihapus', $log->aksi);
        $this->assertSame(Ranting::class, $log->subject_type);
        $this->assertSame('Melak Ulu', $log->before['nama']);
        $this->assertNull($log->after);
    }

    /** Penghapusan yang ditolak tidak boleh meninggalkan baris audit. */
    public function test_penghapusan_yang_ditolak_tidak_menulis_audit(): void
    {
        $this->actingAs($this->penggunaDengan(['is_sekben']));
        $wilayah = Wilayah::factory()->create();
        Ranting::factory()->create(['wilayah_id' => $wilayah->id]);

        $sebelum = AuditLog::where('aksi', 'dihapus')->count();

        try {
            $wilayah->delete();
        } catch (HapusIndukException) {
            // memang diharapkan gagal
        }

        $this->assertSame($sebelum, AuditLog::where('aksi', 'dihapus')->count());
    }

    // --- Penolakan hapus sebagaimana dialami pengurus di panel ---------------

    /**
     * Yang diuji di sini bukan pagar modelnya, melainkan apa yang benar-benar
     * dilihat pengurus: menekan Hapus pada wilayah bertautan memunculkan
     * notifikasi berbahasa Indonesia, bukan halaman galat.
     */
    public function test_panel_menolak_hapus_dengan_notifikasi_bukan_galat(): void
    {
        $this->actingAs($this->penggunaDengan(['is_sekben']));

        $wilayah = Wilayah::factory()->create(['nama' => 'Kutai Barat']);
        Ranting::factory()->count(2)->create(['wilayah_id' => $wilayah->id]);

        Livewire::test(ListWilayahs::class)
            ->callAction(TestAction::make('delete')->table($wilayah))
            ->assertNotified('Wilayah ini tidak bisa dihapus');

        $this->assertModelExists($wilayah);
    }

    public function test_panel_meloloskan_hapus_wilayah_kosong(): void
    {
        $this->actingAs($this->penggunaDengan(['is_sekben']));

        $wilayah = Wilayah::factory()->create();

        Livewire::test(ListWilayahs::class)
            ->callAction(TestAction::make('delete')->table($wilayah));

        $this->assertModelMissing($wilayah);
    }
}
