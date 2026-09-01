<?php

namespace Tests\Feature\Panel;

use App\Filament\Resources\Anggota\AnggotaResource;
use App\Filament\Resources\Anggota\Pages\ListAnggota;
use App\Models\Member;
use App\Models\Ranting;
use App\Models\User;
use Filament\Support\Enums\FontFamily;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

/**
 * B-17: daftar dan rincian anggota, HANYA BACA.
 *
 * Yang dijaga di sini ada dua: siapa boleh membaca, dan bahwa tidak ada satu
 * pun jalan mengubah data lewat resource ini. Aksi setujui / ubah status /
 * ubah tingkat & sabuk dikerjakan sesi berikutnya.
 */
class AnggotaTest extends TestCase
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

    // --- B-17: siapa boleh membaca -------------------------------------------

    #[TestWith(['is_guru_besar'])]
    #[TestWith(['is_sekben'])]
    #[TestWith(['is_admin'])]
    #[TestWith(['is_editor'])]
    public function test_semua_pemegang_hak_akses_boleh_membaca(string $bendera): void
    {
        $this->actingAs($this->penggunaDengan([$bendera]));

        $this->assertTrue(AnggotaResource::canViewAny(), $bendera);
    }

    /** Editor termasuk — membaca dipisah tegas dari mengubah. */
    public function test_editor_bisa_membuka_daftar(): void
    {
        $editor = $this->penggunaDengan(['is_editor']);
        Member::factory()->create();

        $this->actingAs($editor)
            ->get(AnggotaResource::getUrl('index'))
            ->assertSuccessful();
    }

    public function test_anggota_tanpa_hak_akses_ditolak(): void
    {
        $anggota = $this->penggunaDengan([]);

        $this->assertFalse($anggota->can('viewAny', Member::class));

        $this->actingAs($anggota)
            ->get(AnggotaResource::getUrl('index'))
            ->assertForbidden();
    }

    public function test_tamu_dialihkan_ke_masuk(): void
    {
        $this->get(AnggotaResource::getUrl('index'))->assertRedirect(route('masuk'));
    }

    // --- Tidak ada aksi yang mengubah data -----------------------------------

    public function test_tidak_bisa_membuat_menyunting_atau_menghapus(): void
    {
        $this->actingAs($this->penggunaDengan(['is_guru_besar']));
        $anggota = Member::factory()->create();

        $this->assertFalse(AnggotaResource::canCreate());
        $this->assertFalse(AnggotaResource::canEdit($anggota));
        $this->assertFalse(AnggotaResource::canDelete($anggota));
        $this->assertFalse(AnggotaResource::canDeleteAny());
    }

    /** Halaman buat dan sunting memang tidak didaftarkan sama sekali. */
    public function test_hanya_punya_halaman_daftar_dan_lihat(): void
    {
        $this->assertSame(
            ['index', 'view'],
            array_keys(AnggotaResource::getPages()),
        );
    }

    /**
     * Guru Besar pun tidak menemukan aksi pengubah data di tabel ini — sesi
     * ini memang belum memasangnya.
     */
    public function test_tabel_hanya_punya_aksi_lihat(): void
    {
        $this->actingAs($this->penggunaDengan(['is_guru_besar']));
        Member::factory()->create();

        $komponen = Livewire::test(ListAnggota::class);

        $komponen->assertTableActionExists('view');

        foreach (['edit', 'delete', 'setujui', 'ubahStatus', 'resetSandi'] as $aksi) {
            $komponen->assertTableActionDoesNotExist($aksi);
        }
    }

    public function test_tidak_ada_aksi_massal(): void
    {
        $this->actingAs($this->penggunaDengan(['is_guru_besar']));
        Member::factory()->count(2)->create();

        Livewire::test(ListAnggota::class)
            ->assertTableBulkActionDoesNotExist('delete');
    }

    // --- Isi tabel -----------------------------------------------------------

    public function test_kolom_yang_diminta_spek_ada_semua(): void
    {
        $this->actingAs($this->penggunaDengan(['is_sekben']));
        Member::factory()->create();

        $komponen = Livewire::test(ListAnggota::class);

        foreach (['user.nama', 'nia', 'tingkat_keanggotaan', 'tingkatan', 'status', 'ranting.nama'] as $kolom) {
            $komponen->assertTableColumnExists($kolom);
        }
    }

    /**
     * Skenario uji 2: urutan bawaan memakai tingkatan_urutan menurun, bukan
     * enum `tingkatan`. Putih Warga 3 paling atas, Hitam/Polos paling bawah.
     */
    public function test_urutan_bawaan_memakai_tingkatan_urutan_menurun(): void
    {
        $this->actingAs($this->penggunaDengan(['is_sekben']));

        $polos = Member::factory()->create(['tingkatan' => 'hitam_polos']);
        $putih = Member::factory()->create(['tingkatan' => 'putih_warga_3']);
        $oren = Member::factory()->create(['tingkatan' => 'oren']);

        Livewire::test(ListAnggota::class)
            ->assertCanSeeTableRecords([$putih, $oren, $polos], inOrder: true);
    }

    /** Kolom sabuk pun diurutkan lewat tingkatan_urutan, bukan enumnya. */
    public function test_kolom_sabuk_diurutkan_lewat_kolom_urutan(): void
    {
        $this->actingAs($this->penggunaDengan(['is_sekben']));

        $polos = Member::factory()->create(['tingkatan' => 'hitam_polos']);
        $putih = Member::factory()->create(['tingkatan' => 'putih_warga_3']);

        Livewire::test(ListAnggota::class)
            ->sortTable('tingkatan')
            ->assertCanSeeTableRecords([$polos, $putih], inOrder: true);
    }

    public function test_label_sabuk_mengikuti_peta_di_spek(): void
    {
        $this->actingAs($this->penggunaDengan(['is_sekben']));
        $anggota = Member::factory()->create(['tingkatan' => 'merah_warga_1']);

        Livewire::test(ListAnggota::class)
            ->assertTableColumnFormattedStateSet('tingkatan', 'Merah — Warga Tingkat 1', $anggota);
    }

    public function test_label_status_dan_tingkat_terbaca(): void
    {
        $this->actingAs($this->penggunaDengan(['is_sekben']));
        $anggota = Member::factory()->create([
            'status' => 'non_aktif',
            'tingkat_keanggotaan' => 'warga',
        ]);

        Livewire::test(ListAnggota::class)
            ->assertTableColumnFormattedStateSet('status', 'Non-aktif', $anggota)
            ->assertTableColumnFormattedStateSet('tingkat_keanggotaan', 'Warga', $anggota);
    }

    /**
     * B-1: pendaftar yang belum disetujui belum punya nia. Yang tampil harus
     * terbaca sebagai keadaan wajar, bukan kolom yang gagal terisi.
     */
    public function test_anggota_pending_menampilkan_nia_kosong_dengan_wajar(): void
    {
        $this->actingAs($this->penggunaDengan(['is_sekben']));
        $pending = Member::factory()->create(['status' => 'pending']);

        $this->assertNull($pending->nia);

        Livewire::test(ListAnggota::class)
            ->assertCanSeeTableRecords([$pending])
            ->assertSee('Belum terbit');
    }

    public function test_nia_dan_no_warga_memakai_font_mono(): void
    {
        $this->actingAs($this->penggunaDengan(['is_sekben']));
        Member::factory()->create();

        $tabel = Livewire::test(ListAnggota::class)->instance()->getTable();

        foreach (['nia', 'no_warga'] as $kolom) {
            $this->assertSame(
                FontFamily::Mono,
                $tabel->getColumn($kolom)->getFontFamily(),
                $kolom,
            );
        }
    }

    // --- Filter --------------------------------------------------------------

    public function test_keempat_filter_yang_diminta_ada(): void
    {
        $this->actingAs($this->penggunaDengan(['is_sekben']));
        Member::factory()->create();

        $komponen = Livewire::test(ListAnggota::class);

        foreach (['tingkat_keanggotaan', 'tingkatan', 'status', 'ranting_id'] as $filter) {
            $komponen->assertTableFilterExists($filter);
        }
    }

    public function test_filter_status_menyaring(): void
    {
        $this->actingAs($this->penggunaDengan(['is_sekben']));

        $aktif = Member::factory()->create(['status' => 'aktif']);
        $pending = Member::factory()->create(['status' => 'pending']);

        Livewire::test(ListAnggota::class)
            ->filterTable('status', 'aktif')
            ->assertCanSeeTableRecords([$aktif])
            ->assertCanNotSeeTableRecords([$pending]);
    }

    public function test_filter_ranting_menyaring(): void
    {
        $this->actingAs($this->penggunaDengan(['is_sekben']));

        $ranting = Ranting::factory()->create();
        $di = Member::factory()->create(['ranting_id' => $ranting->id]);
        $luar = Member::factory()->create(['ranting_id' => null]);

        Livewire::test(ListAnggota::class)
            ->filterTable('ranting_id', $ranting->id)
            ->assertCanSeeTableRecords([$di])
            ->assertCanNotSeeTableRecords([$luar]);
    }

    public function test_filter_tingkat_keanggotaan_menyaring(): void
    {
        $this->actingAs($this->penggunaDengan(['is_sekben']));

        $warga = Member::factory()->create(['tingkat_keanggotaan' => 'warga']);
        $anggota = Member::factory()->create(['tingkat_keanggotaan' => 'anggota']);

        Livewire::test(ListAnggota::class)
            ->filterTable('tingkat_keanggotaan', 'warga')
            ->assertCanSeeTableRecords([$warga])
            ->assertCanNotSeeTableRecords([$anggota]);
    }

    // --- Halaman rincian -----------------------------------------------------

    public function test_halaman_lihat_terbuka_untuk_yang_berhak(): void
    {
        $editor = $this->penggunaDengan(['is_editor']);
        $anggota = Member::factory()->create();

        $this->actingAs($editor)
            ->get(AnggotaResource::getUrl('view', ['record' => $anggota]))
            ->assertSuccessful();
    }

    public function test_halaman_lihat_ditolak_untuk_yang_tidak_berhak(): void
    {
        $biasa = $this->penggunaDengan([]);
        $anggota = Member::factory()->create();

        $this->actingAs($biasa)
            ->get(AnggotaResource::getUrl('view', ['record' => $anggota]))
            ->assertForbidden();
    }

    /** Slug mengikuti spek: /admin/anggota, bukan /admin/members. */
    public function test_slug_sesuai_spek(): void
    {
        $this->assertStringEndsWith('/admin/anggota', AnggotaResource::getUrl('index'));
    }

    /**
     * B-1: pendaftar pending belum punya nia, jadi judul halaman rinciannya
     * akan kosong kalau memakai nia. Memakai nama orangnya menutup itu.
     */
    public function test_judul_rincian_memakai_nama_bukan_nia(): void
    {
        $anggota = Member::factory()->create(['status' => 'pending']);

        $this->assertNull($anggota->nia);
        $this->assertSame(
            $anggota->user->nama,
            AnggotaResource::getRecordTitle($anggota),
        );
    }
}
