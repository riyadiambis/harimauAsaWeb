<?php

namespace Tests\Feature\Keanggotaan;

use App\Models\Member;
use App\Models\Ranting;
use App\Models\User;
use App\Support\PenomoranNia;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class KeanggotaanTest extends TestCase
{
    use RefreshDatabase;

    /** Skenario 1. */
    #[TestWith(['hitam_polos', 1])]
    #[TestWith(['kuning', 2])]
    #[TestWith(['oren', 3])]
    #[TestWith(['merah_warga_1', 4])]
    #[TestWith(['merah_warga_2', 5])]
    #[TestWith(['putih_warga_3', 6])]
    public function test_mutator_tingkatan_mengisi_urutan(string $tingkatan, int $urutan): void
    {
        $member = Member::factory()->create(['tingkatan' => $tingkatan]);

        $this->assertSame($urutan, $member->fresh()->tingkatan_urutan);
    }

    public function test_tingkatan_urutan_tidak_bisa_diketik_manual(): void
    {
        $member = Member::factory()->create(['tingkatan' => 'oren']);

        $member->update(['tingkatan_urutan' => 99]);

        $this->assertSame(3, $member->fresh()->tingkatan_urutan);
    }

    /** Skenario 2. */
    public function test_pengurutan_sabuk_tertinggi_ke_terendah(): void
    {
        foreach (['oren', 'putih_warga_3', 'hitam_polos', 'merah_warga_2'] as $t) {
            Member::factory()->create(['tingkatan' => $t]);
        }

        $urut = Member::orderByDesc('tingkatan_urutan')->pluck('tingkatan')->all();

        $this->assertSame(['putih_warga_3', 'merah_warga_2', 'oren', 'hitam_polos'], $urut);
    }

    public function test_label_tingkatan_sesuai_peta_di_spek(): void
    {
        $member = Member::factory()->create(['tingkatan' => 'merah_warga_1']);

        $this->assertSame('Merah — Warga Tingkat 1', $member->labelTingkatan());
    }

    /** B-1: nia baru ada setelah disetujui. */
    public function test_pendaftar_pending_belum_punya_nia(): void
    {
        $member = Member::factory()->create(['status' => 'pending']);

        $this->assertNull($member->fresh()->nia);
    }

    public function test_nia_diberikan_saat_status_beranjak_dari_pending(): void
    {
        $member = Member::factory()->create([
            'status' => 'pending',
            'tanggal_gabung' => '2026-04-01',
        ]);

        $member->update(['status' => 'aktif']);

        $this->assertSame('2026-0001', $member->fresh()->nia);
    }

    /** Format: tahun bergabung + nomor urut, urut per tahun. */
    public function test_nomor_urut_nia_berjalan_per_tahun(): void
    {
        $a = Member::factory()->create(['status' => 'pending', 'tanggal_gabung' => '2026-01-10']);
        $b = Member::factory()->create(['status' => 'pending', 'tanggal_gabung' => '2026-09-20']);
        $c = Member::factory()->create(['status' => 'pending', 'tanggal_gabung' => '2027-02-02']);

        foreach ([$a, $b, $c] as $member) {
            $member->update(['status' => 'aktif']);
        }

        $this->assertSame('2026-0001', $a->fresh()->nia);
        $this->assertSame('2026-0002', $b->fresh()->nia);
        // Ganti tahun → nomor urut mulai lagi dari 1.
        $this->assertSame('2027-0001', $c->fresh()->nia);
    }

    public function test_nia_tidak_berubah_saat_status_berganti_lagi(): void
    {
        $member = Member::factory()->create(['status' => 'pending', 'tanggal_gabung' => '2026-04-01']);
        $member->update(['status' => 'aktif']);
        $nia = $member->fresh()->nia;

        $member->update(['status' => 'non_aktif']);

        $this->assertSame($nia, $member->fresh()->nia);
    }

    public function test_nia_unik_di_level_database(): void
    {
        Member::factory()->create(['status' => 'aktif', 'nia' => '2026-0001']);

        $this->expectException(UniqueConstraintViolationException::class);

        Member::factory()->create(['status' => 'aktif', 'nia' => '2026-0001']);
    }

    public function test_format_nia_dipadkan_empat_digit(): void
    {
        $this->assertSame('2026-0007', PenomoranNia::format(2026, 7));
        $this->assertSame('2026-0042', PenomoranNia::format(2026, 42));
    }

    public function test_relasi_member_ke_ranting_dan_wilayah(): void
    {
        $ranting = Ranting::factory()->create();
        $member = Member::factory()->create(['ranting_id' => $ranting->id]);

        $this->assertSame($ranting->id, $member->ranting->id);
        $this->assertSame($ranting->wilayah_id, $member->ranting->wilayah->id);
        $this->assertTrue($member->user instanceof User);
    }
}
