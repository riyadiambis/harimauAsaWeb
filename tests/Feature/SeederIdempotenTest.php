<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Member;
use App\Models\User;
use Database\Seeders\AkunUjiSeeder;
use Database\Seeders\KeanggotaanUjiSeeder;
use Database\Seeders\WilayahRantingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Spek fitur 02 mengklaim "semua seeder aman dijalankan ulang tanpa
 * migrate:fresh". Klaim itu sempat tidak benar: KeanggotaanUjiSeeder hanya
 * MELENGKAPI kolom yang masih kosong, jadi akun uji yang sudah terlanjur diubah
 * lewat panel tidak pernah kembali ke keadaan awal.
 *
 * Yang paling terasa: pendingcoba1 sengaja dibiarkan pending untuk menguji A-6.
 * Begitu ia disetujui lewat panel, tidak ada jalan otomatis mengembalikannya —
 * statusnya tetap aktif dan nia-nya tetap terbit.
 */
class SeederIdempotenTest extends TestCase
{
    use RefreshDatabase;

    private function seedSemua(): void
    {
        $this->seed(AkunUjiSeeder::class);
        $this->seed(WilayahRantingSeeder::class);
        $this->seed(KeanggotaanUjiSeeder::class);
    }

    /**
     * @return array<string, mixed>
     */
    private function potret(string $username): array
    {
        $user = User::where('username', $username)->firstOrFail();
        $m = $user->member;

        return [
            'nama' => $user->nama,
            'harus_ganti_sandi' => $user->harus_ganti_sandi,
            'is_admin' => $user->is_admin,
            'is_guru_besar' => $user->is_guru_besar,
            'is_sekben' => $user->is_sekben,
            'is_editor' => $user->is_editor,
            'status' => $m->status,
            'nia' => $m->nia,
            'no_warga' => $m->no_warga,
            'tingkat_keanggotaan' => $m->tingkat_keanggotaan,
            'tingkatan' => $m->tingkatan,
            'tingkatan_urutan' => $m->tingkatan_urutan,
            'ranting_id' => $m->ranting_id,
            'tanggal_naik_warga' => $m->tanggal_naik_warga?->toDateString(),
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function potretSemua(): array
    {
        return collect(array_keys(AkunUjiSeeder::DAFTAR))
            ->mapWithKeys(fn (string $u): array => [$u => $this->potret($u)])
            ->all();
    }

    // --- Idempoten tanpa perubahan apa pun -----------------------------------

    public function test_dijalankan_dua_kali_menghasilkan_keadaan_yang_sama(): void
    {
        $this->seedSemua();
        $sebelum = $this->potretSemua();

        $this->seedSemua();

        $this->assertSame($sebelum, $this->potretSemua());
    }

    public function test_tidak_menggandakan_baris(): void
    {
        $this->seedSemua();
        $jumlahUser = User::count();
        $jumlahMember = Member::count();

        $this->seedSemua();

        $this->assertSame($jumlahUser, User::count());
        $this->assertSame($jumlahMember, Member::count());
    }

    // --- Memulihkan keadaan yang sudah diubah --------------------------------

    /**
     * Inti Bagian 0: keadaan awal dipulihkan, bukan sekadar dilengkapi.
     */
    public function test_memulihkan_seluruh_kolom_yang_diubah(): void
    {
        $this->seedSemua();
        $awal = $this->potretSemua();

        // Diacak lewat model, meniru perubahan yang terjadi lewat panel.
        $pending = User::where('username', 'pendingcoba1')->first()->member;
        $pending->status = 'aktif';
        $pending->nia = '2099-0099';
        $pending->tingkat_keanggotaan = 'warga';
        $pending->tingkatan = 'putih_warga_3';
        $pending->no_warga = '12345678';
        $pending->tanggal_naik_warga = '2020-01-01';
        $pending->save();

        $warga = User::where('username', 'gurubesar')->first()->member;
        $warga->tingkat_keanggotaan = 'anggota';
        $warga->tingkatan = 'hitam_polos';
        $warga->tanggal_naik_warga = null;
        $warga->ranting_id = null;
        $warga->save();

        // is_admin sengaja TIDAK dicabut di sini: B-14 melarang mencabut hak
        // admin terakhir, dan pagar itu memang harus menang atas seeder.
        $admin = User::where('username', 'adminmin')->first();
        $admin->harus_ganti_sandi = true;
        $admin->is_editor = true;
        $admin->nama = 'Nama Terganti';
        $admin->save();

        $this->seedSemua();

        $this->assertSame($awal, $this->potretSemua());
    }

    /** pendingcoba1 kembali pending TANPA nia — kasus yang memicu perbaikan ini. */
    public function test_pendingcoba1_kembali_pending_tanpa_nia(): void
    {
        $this->seedSemua();

        $member = User::where('username', 'pendingcoba1')->first()->member;
        $member->status = 'aktif';
        $member->nia = '2026-0002';
        $member->save();

        $this->seedSemua();

        $member->refresh();

        $this->assertSame('pending', $member->status);
        $this->assertNull($member->nia, 'B-1: pendaftar pending tidak punya nia');
    }

    /** Kolom yang keadaan awalnya null harus kembali null, bukan dibiarkan. */
    public function test_kolom_bernilai_awal_null_dikembalikan_null(): void
    {
        $this->seedSemua();

        $anggota = User::where('username', 'anggotacoba1')->first()->member;
        $anggota->no_warga = '87654321';
        $anggota->tanggal_naik_warga = '2021-05-05';
        $anggota->save();

        $this->seedSemua();

        $anggota->refresh();

        $this->assertNull($anggota->no_warga);
        $this->assertNull($anggota->tanggal_naik_warga);
    }

    /** B-12: nia yang sudah sah tidak diganti nomor baru tiap kali seeding. */
    public function test_nia_yang_sudah_ada_tidak_berubah(): void
    {
        $this->seedSemua();

        $nia = collect(array_keys(AkunUjiSeeder::DAFTAR))
            ->mapWithKeys(fn (string $u): array => [
                $u => User::where('username', $u)->first()->member->nia,
            ]);

        $this->seedSemua();
        $this->seedSemua();

        foreach ($nia as $username => $nilai) {
            $this->assertSame(
                $nilai,
                User::where('username', $username)->first()->member->nia,
                $username,
            );
        }
    }

    // --- Akun di luar daftar tidak tersentuh ---------------------------------

    /**
     * Pendaftaran sungguhan (mis. lewat /daftar) tidak boleh ikut dipulihkan
     * atau diubah oleh seeder.
     */
    public function test_akun_di_luar_daftar_tidak_tersentuh(): void
    {
        $this->seedSemua();

        $luar = User::factory()->create(['username' => 'riyadisan', 'nama' => 'Rahmat Riyadi']);
        $member = Member::factory()->create([
            'user_id' => $luar->id,
            'status' => 'aktif',
            'nia' => '2026-0900',
            'no_warga' => '55556666',
            'tingkat_keanggotaan' => 'warga',
            'tingkatan' => 'merah_warga_1',
            'tanggal_naik_warga' => '2025-03-03',
        ]);

        $potret = fn (Member $m): array => [
            'status' => $m->status,
            'nia' => $m->nia,
            'no_warga' => $m->no_warga,
            'tingkat_keanggotaan' => $m->tingkat_keanggotaan,
            'tingkatan' => $m->tingkatan,
            'tanggal_naik_warga' => $m->tanggal_naik_warga?->toDateString(),
        ];

        $sebelum = $potret($member->fresh());

        $this->seedSemua();

        $this->assertSame($sebelum, $potret($member->fresh()));
        $this->assertSame('Rahmat Riyadi', $luar->fresh()->nama);
    }

    // --- Seeding tidak meninggalkan jejak audit ------------------------------

    /**
     * Spek: "proses seeding tidak meninggalkan baris audit". Sebelumnya itu
     * hanya benar lewat DatabaseSeeder; menjalankan satu seeder sendirian
     * meninggalkan baris audit palsu seolah ada pengurus yang mengubah data.
     * WithoutModelEvents sekarang dipasang di tiap seedernya.
     */
    public function test_seeding_tidak_menulis_audit_log(): void
    {
        $this->seedSemua();
        $this->assertSame(0, AuditLog::count());

        $member = User::where('username', 'gurubesar')->first()->member;
        $member->tingkatan = 'kuning';
        $member->save();

        $sesudahDiubah = AuditLog::count();
        $this->assertSame(1, $sesudahDiubah, 'perubahan biasa tetap beraudit');

        $this->seedSemua();

        $this->assertSame($sesudahDiubah, AuditLog::count(), 'seeder tidak menambah audit');
    }

    // --- Satu sumber kebenaran -----------------------------------------------

    /**
     * Daftar akun uji dulu diketik dua kali di dua seeder. Sekarang satu.
     */
    public function test_daftar_akun_uji_lengkap_dan_konsisten(): void
    {
        $this->seedSemua();

        foreach (AkunUjiSeeder::DAFTAR as $username => $awal) {
            $user = User::where('username', $username)->first();

            $this->assertNotNull($user, $username);
            $this->assertSame($awal['nama'], $user->nama, $username);
            $this->assertSame($awal['status'], $user->member->status, $username);
            $this->assertSame($awal['tingkatan'], $user->member->tingkatan, $username);
        }

        $this->assertSame(count(AkunUjiSeeder::DAFTAR), User::count());
    }
}
