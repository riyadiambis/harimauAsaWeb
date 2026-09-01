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
 * B-5: menyetujui pendaftar dan mengubah status anggota.
 *
 * Dua hal yang dijaga selain hasilnya sendiri:
 *   1. Aksi yang tidak diizinkan TIDAK TAMPIL, bukan tampil lalu gagal diklik.
 *   2. Penerbitan NIA (B-12) dan penulisan audit (B-10) datang dari hook model
 *      yang sudah ada, bukan logika kedua yang ditulis ulang di resource.
 */
class AksiStatusAnggotaTest extends TestCase
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

    // --- B-5: siapa yang melihat aksinya -------------------------------------

    #[TestWith(['is_guru_besar'])]
    #[TestWith(['is_sekben'])]
    public function test_guru_besar_dan_sekben_melihat_kedua_aksi(string $bendera): void
    {
        $this->actingAs($this->penggunaDengan([$bendera]));

        $pending = Member::factory()->create(['status' => 'pending']);
        $aktif = Member::factory()->create(['status' => 'aktif']);

        Livewire::test(ListAnggota::class)
            ->assertActionVisible($this->aksi('setujui', $pending))
            ->assertActionVisible($this->aksi('ubahStatus', $aktif));
    }

    /**
     * B-5 mengecualikan Admin. Ia lolos B-15 dan boleh membaca daftar (B-17),
     * tapi kedua aksi ini tidak boleh tampil untuknya.
     */
    public function test_admin_tidak_melihat_satu_pun_aksi(): void
    {
        $this->actingAs($this->penggunaDengan(['is_admin']));

        $pending = Member::factory()->create(['status' => 'pending']);
        $aktif = Member::factory()->create(['status' => 'aktif']);

        Livewire::test(ListAnggota::class)
            ->assertActionHidden($this->aksi('setujui', $pending))
            ->assertActionHidden($this->aksi('ubahStatus', $aktif));
    }

    public function test_editor_tidak_melihat_satu_pun_aksi(): void
    {
        $this->actingAs($this->penggunaDengan(['is_editor']));

        $pending = Member::factory()->create(['status' => 'pending']);
        $aktif = Member::factory()->create(['status' => 'aktif']);

        Livewire::test(ListAnggota::class)
            ->assertActionHidden($this->aksi('setujui', $pending))
            ->assertActionHidden($this->aksi('ubahStatus', $aktif));
    }

    /** Admin yang juga Sekben lolos lewat bendera Sekben-nya, bukan Admin-nya. */
    public function test_admin_yang_juga_sekben_melihat_aksinya(): void
    {
        $this->actingAs($this->penggunaDengan(['is_admin', 'is_sekben']));

        $pending = Member::factory()->create(['status' => 'pending']);

        Livewire::test(ListAnggota::class)
            ->assertActionVisible($this->aksi('setujui', $pending));
    }

    // --- Aksi muncul pada baris yang tepat -----------------------------------

    /** "Setujui" hanya masuk akal untuk yang masih pending. */
    #[TestWith(['aktif'])]
    #[TestWith(['non_aktif'])]
    #[TestWith(['alumni'])]
    public function test_setujui_tidak_tampil_pada_yang_bukan_pending(string $status): void
    {
        $this->actingAs($this->penggunaDengan(['is_sekben']));

        $anggota = Member::factory()->create(['status' => $status]);

        Livewire::test(ListAnggota::class)
            ->assertActionHidden($this->aksi('setujui', $anggota));
    }

    /**
     * "Ubah status" tidak tampil pada pending. Spek tidak punya jalur menolak
     * pendaftar, dan membiarkan aksi ini di sana akan jadi jalur tolak dadakan
     * yang justru MENERBITKAN NIA — hook menyala pada setiap perpindahan keluar
     * dari pending, bertentangan dengan B-1.
     */
    public function test_ubah_status_tidak_tampil_pada_pending(): void
    {
        $this->actingAs($this->penggunaDengan(['is_sekben']));

        $pending = Member::factory()->create(['status' => 'pending']);

        Livewire::test(ListAnggota::class)
            ->assertActionHidden($this->aksi('ubahStatus', $pending));
    }

    // --- Setujui pendaftar (B-5 + B-12) --------------------------------------

    public function test_setujui_mengaktifkan_dan_menerbitkan_nia(): void
    {
        $this->actingAs($this->penggunaDengan(['is_sekben']));

        $pending = Member::factory()->create([
            'status' => 'pending',
            'tanggal_gabung' => '2026-04-10',
        ]);

        $this->assertNull($pending->nia);

        Livewire::test(ListAnggota::class)
            ->callAction($this->aksi('setujui', $pending));

        $pending->refresh();

        $this->assertSame('aktif', $pending->status);
        $this->assertSame('2026-0001', $pending->nia);
    }

    /**
     * B-12 lewat UI harus menghasilkan penomoran yang sama dengan lewat Tinker:
     * berurut per tahun bergabung.
     */
    public function test_dua_pendaftar_berurutan_di_tahun_yang_sama(): void
    {
        $this->actingAs($this->penggunaDengan(['is_guru_besar']));

        $satu = Member::factory()->create(['status' => 'pending', 'tanggal_gabung' => '2026-03-01']);
        $dua = Member::factory()->create(['status' => 'pending', 'tanggal_gabung' => '2026-03-02']);

        $komponen = Livewire::test(ListAnggota::class);
        $komponen->callAction($this->aksi('setujui', $satu));
        $komponen->callAction($this->aksi('setujui', $dua));

        $this->assertSame('2026-0001', $satu->refresh()->nia);
        $this->assertSame('2026-0002', $dua->refresh()->nia);
    }

    /** Tahun berganti → nomor urut mulai lagi dari 1 (B-12). */
    public function test_tahun_berikutnya_mulai_dari_satu_lagi(): void
    {
        $this->actingAs($this->penggunaDengan(['is_sekben']));

        $lama = Member::factory()->create(['status' => 'pending', 'tanggal_gabung' => '2026-05-01']);
        $baru = Member::factory()->create(['status' => 'pending', 'tanggal_gabung' => '2027-01-05']);

        $komponen = Livewire::test(ListAnggota::class);
        $komponen->callAction($this->aksi('setujui', $lama));
        $komponen->callAction($this->aksi('setujui', $baru));

        $this->assertSame('2026-0001', $lama->refresh()->nia);
        $this->assertSame('2027-0001', $baru->refresh()->nia);
    }

    /**
     * Bukti bahwa yang menyala hook model, bukan logika kedua di resource:
     * penomoran lewat UI melanjutkan deret yang sudah ada di database, termasuk
     * nomor yang dibuat di luar panel.
     */
    public function test_penomoran_lewat_ui_melanjutkan_deret_yang_sudah_ada(): void
    {
        $this->actingAs($this->penggunaDengan(['is_sekben']));

        Member::factory()->create(['status' => 'aktif', 'nia' => '2026-0007']);
        $pending = Member::factory()->create(['status' => 'pending', 'tanggal_gabung' => '2026-08-01']);

        Livewire::test(ListAnggota::class)
            ->callAction($this->aksi('setujui', $pending));

        $this->assertSame('2026-0008', $pending->refresh()->nia);
    }

    // --- Ubah status (B-5) ---------------------------------------------------

    #[TestWith(['aktif', 'non_aktif'])]
    #[TestWith(['aktif', 'alumni'])]
    #[TestWith(['non_aktif', 'aktif'])]
    #[TestWith(['alumni', 'aktif'])]
    public function test_ubah_status_antara_ketiganya(string $dari, string $ke): void
    {
        $this->actingAs($this->penggunaDengan(['is_sekben']));

        $anggota = Member::factory()->create(['status' => $dari]);

        Livewire::test(ListAnggota::class)
            ->callAction($this->aksi('ubahStatus', $anggota), ['status' => $ke]);

        $this->assertSame($ke, $anggota->refresh()->status);
    }

    /**
     * `pending` tidak ditawarkan dan tidak bisa diselundupkan lewat request:
     * Select memvalidasi terhadap daftar opsinya, jadi tidak ada jalan kembali
     * ke pending.
     */
    public function test_pending_tidak_bisa_dipilih_sebagai_status(): void
    {
        $this->actingAs($this->penggunaDengan(['is_sekben']));
        $anggota = Member::factory()->create(['status' => 'aktif']);

        Livewire::test(ListAnggota::class)
            ->callAction($this->aksi('ubahStatus', $anggota), ['status' => 'pending'])
            ->assertHasActionErrors(['status']);

        $this->assertSame('aktif', $anggota->refresh()->status);
    }

    /**
     * B-12: "tidak berubah lagi setelah diberikan". Alumni yang dikembalikan
     * jadi aktif memakai NIA lamanya, tidak terbit yang baru.
     */
    public function test_alumni_diaktifkan_lagi_memakai_nia_lama(): void
    {
        $this->actingAs($this->penggunaDengan(['is_guru_besar']));

        $alumni = Member::factory()->create(['status' => 'alumni', 'nia' => '2021-0003']);

        Livewire::test(ListAnggota::class)
            ->callAction($this->aksi('ubahStatus', $alumni), ['status' => 'aktif']);

        $alumni->refresh();

        $this->assertSame('aktif', $alumni->status);
        $this->assertSame('2021-0003', $alumni->nia);
    }

    // --- Audit log (B-10) ----------------------------------------------------

    public function test_persetujuan_tertulis_di_audit_log(): void
    {
        $pengurus = $this->penggunaDengan(['is_sekben']);
        $this->actingAs($pengurus);

        $pending = Member::factory()->create(['status' => 'pending']);

        Livewire::test(ListAnggota::class)
            ->callAction($this->aksi('setujui', $pending));

        $log = AuditLog::latest('id')->first();

        $this->assertSame('diubah', $log->aksi);
        $this->assertSame(Member::class, $log->subject_type);
        $this->assertSame($pending->id, (int) $log->subject_id);
        $this->assertSame($pengurus->id, (int) $log->actor_id);
        $this->assertSame('pending', $log->before['status']);
        $this->assertSame('aktif', $log->after['status']);
    }

    public function test_perubahan_status_tertulis_di_audit_log(): void
    {
        $pengurus = $this->penggunaDengan(['is_guru_besar']);
        $this->actingAs($pengurus);

        $anggota = Member::factory()->create(['status' => 'aktif']);

        Livewire::test(ListAnggota::class)
            ->callAction($this->aksi('ubahStatus', $anggota), ['status' => 'alumni']);

        $log = AuditLog::latest('id')->first();

        $this->assertSame('diubah', $log->aksi);
        $this->assertSame($pengurus->id, (int) $log->actor_id);
        $this->assertSame(['status' => 'aktif'], $log->before);
        $this->assertSame(['status' => 'alumni'], $log->after);
    }

    /** Waktunya WITA (BR-16), bukan UTC. */
    public function test_audit_dicatat_dengan_waktu_wita(): void
    {
        $this->actingAs($this->penggunaDengan(['is_sekben']));
        $anggota = Member::factory()->create(['status' => 'aktif']);

        Livewire::test(ListAnggota::class)
            ->callAction($this->aksi('ubahStatus', $anggota), ['status' => 'non_aktif']);

        $log = AuditLog::latest('id')->first();

        $this->assertLessThanOrEqual(
            60,
            abs($log->created_at->diffInSeconds(Carbon::now())),
        );
    }
}
