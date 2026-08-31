<?php

namespace Tests\Feature\Keanggotaan;

use App\Models\AuditLog;
use App\Models\Jabatan;
use App\Models\Member;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Skenario 9 / B-10: perubahan pada B-2, B-4, B-5, dan B-6 wajib tercatat
 * beserta nilai sebelum dan sesudah.
 */
class AuditLogTest extends TestCase
{
    use RefreshDatabase;

    private function logTerakhir(): AuditLog
    {
        return AuditLog::latest('id')->firstOrFail();
    }

    /** B-2. */
    public function test_perubahan_sabuk_tercatat_dengan_nilai_sebelum_dan_sesudah(): void
    {
        $member = Member::factory()->create(['tingkatan' => 'kuning']);

        $member->update(['tingkatan' => 'oren']);

        $log = $this->logTerakhir();

        $this->assertSame('diubah', $log->aksi);
        $this->assertSame(Member::class, $log->subject_type);
        $this->assertSame($member->id, $log->subject_id);
        $this->assertSame('kuning', $log->before['tingkatan']);
        $this->assertSame('oren', $log->after['tingkatan']);
    }

    /** B-2. */
    public function test_perubahan_tingkat_keanggotaan_dan_no_warga_tercatat(): void
    {
        $member = Member::factory()->create();

        $member->update(['tingkat_keanggotaan' => 'warga', 'no_warga' => 'W-001']);

        $log = $this->logTerakhir();

        $this->assertSame('anggota', $log->before['tingkat_keanggotaan']);
        $this->assertSame('warga', $log->after['tingkat_keanggotaan']);
        $this->assertSame('W-001', $log->after['no_warga']);
    }

    /** B-5. */
    public function test_perubahan_status_anggota_tercatat(): void
    {
        $member = Member::factory()->create(['status' => 'pending']);

        $member->update(['status' => 'aktif']);

        $log = $this->logTerakhir();

        $this->assertSame('pending', $log->before['status']);
        $this->assertSame('aktif', $log->after['status']);
    }

    /** B-6. */
    public function test_pemberian_hak_akses_tercatat(): void
    {
        $user = User::factory()->create();

        $user->is_admin = true;
        $user->save();

        $log = $this->logTerakhir();

        $this->assertSame(User::class, $log->subject_type);
        $this->assertFalse((bool) $log->before['is_admin']);
        $this->assertTrue((bool) $log->after['is_admin']);
    }

    /** B-4: mencakup "mengisi", jadi pembuatan jabatan ikut tercatat. */
    public function test_pembuatan_dan_penghapusan_jabatan_tercatat(): void
    {
        $jabatan = Jabatan::factory()->create(['nama_jabatan' => 'Sekben Umum']);

        $this->assertSame('dibuat', $this->logTerakhir()->aksi);
        $this->assertSame('Sekben Umum', $this->logTerakhir()->after['nama_jabatan']);

        $jabatan->delete();

        $this->assertSame('dihapus', $this->logTerakhir()->aksi);
        $this->assertSame('Sekben Umum', $this->logTerakhir()->before['nama_jabatan']);
    }

    /** Pelaku ikut tercatat saat ada pengguna yang sedang masuk. */
    public function test_pelaku_tercatat(): void
    {
        $pengurus = User::factory()->create();
        $member = Member::factory()->create();

        $this->actingAs($pengurus);
        $member->update(['status' => 'non_aktif']);

        $this->assertSame($pengurus->id, $this->logTerakhir()->actor_id);
    }

    public function test_perubahan_dari_konsol_tetap_tercatat_tanpa_pelaku(): void
    {
        $member = Member::factory()->create();

        $member->update(['status' => 'alumni']);

        $this->assertNull($this->logTerakhir()->actor_id);
    }

    /** Kolom di luar B-10 tidak boleh mengotori audit. */
    public function test_kolom_yang_tidak_diawasi_tidak_menghasilkan_baris(): void
    {
        $member = Member::factory()->create();
        $sebelum = AuditLog::count();

        $member->update(['iuran_override' => 15000, 'tanggal_naik_warga' => '2026-01-01']);

        $this->assertSame($sebelum, AuditLog::count());
    }

    public function test_relasi_subject_dan_actor_bisa_ditelusuri(): void
    {
        $pengurus = User::factory()->create();
        $member = Member::factory()->create();

        $this->actingAs($pengurus);
        $member->update(['status' => 'aktif']);

        $log = $this->logTerakhir();

        $this->assertSame($member->id, $log->subject->id);
        $this->assertSame($pengurus->id, $log->actor->id);
    }
}
