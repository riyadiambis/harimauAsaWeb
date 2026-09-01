<?php

namespace Database\Seeders;

use App\Models\Member;
use App\Models\Ranting;
use App\Models\User;
use App\Support\PenomoranNia;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

/**
 * Melengkapi data keanggotaan akun uji — tingkat, sabuk, ranting, nia, no_warga,
 * dan tanggal naik warga. Dipisah supaya AkunUjiSeeder tetap murni soal
 * autentikasi (fitur 01) dan seeder ini yang memegang aturan fitur 02.
 *
 * Daftar akunnya dibaca dari AkunUjiSeeder::DAFTAR — satu sumber kebenaran.
 *
 * Butuh AkunUjiSeeder dan WilayahRantingSeeder sudah jalan lebih dulu.
 *
 * MEMULIHKAN, bukan sekadar melengkapi. Kolom yang keadaan awalnya null
 * dikembalikan ke null, bukan dibiarkan apa adanya — kalau tidak, akun uji yang
 * sempat diubah lewat panel tidak pernah benar-benar kembali ke keadaan awal
 * dan seeder ini cuma tampak idempoten.
 */
class KeanggotaanUjiSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $ranting = Ranting::pluck('id', 'nama');
        $dipulihkan = 0;

        foreach (AkunUjiSeeder::DAFTAR as $username => $awal) {
            $user = User::where('username', $username)->first();

            if ($user === null) {
                continue;
            }

            // Akun di luar daftar tidak pernah tersentuh: loop ini hanya berjalan
            // untuk username yang ada di AkunUjiSeeder::DAFTAR. Pendaftaran
            // sungguhan aman.
            $this->pulihkan(
                $user->member,
                $awal,
                $awal['ranting'] === null ? null : ($ranting[$awal['ranting']] ?? null),
            );
            $dipulihkan++;
        }

        $this->command?->info("  Akun uji dipulihkan: {$dipulihkan}");
        $this->command?->info('  Anggota ber-NIA: '.Member::whereNotNull('nia')->count());
    }

    /**
     * @param  array<string, mixed>  $awal
     */
    private function pulihkan(Member $member, array $awal, ?int $rantingId): void
    {
        $member->tingkat_keanggotaan = $awal['tingkat_keanggotaan'];
        // Lewat mutator, jadi tingkatan_urutan ikut terisi ulang.
        $member->tingkatan = $awal['tingkatan'];
        $member->ranting_id = $rantingId;

        // B-13: tidak ada akun uji yang punya nomor kartu tanda warga. Kalau
        // sempat diisi lewat panel, dikosongkan lagi.
        $member->no_warga = null;

        // B-7: hanya warga yang punya tanggal naik warga. Yang diturunkan lagi
        // jadi anggota harus kehilangan tanggalnya, bukan menyimpan tanggal basi
        // yang nanti dibaca penerbitan tagihan fitur 03.
        // Dipaksa, bukan dipertahankan: tanggal yang sempat diubah lewat panel
        // harus kembali ke nilai awalnya, bukan menetap.
        $member->tanggal_naik_warga = $awal['tingkat_keanggotaan'] === 'warga'
            ? $member->tanggal_gabung->copy()->addMonths(18)
            : null;

        // B-1 dan B-12. Pendaftar yang masih pending TIDAK punya nia; kalau
        // sempat disetujui lewat panel lalu dikembalikan ke pending oleh
        // AkunUjiSeeder, nomornya ikut dicabut supaya keadaannya benar-benar
        // seperti semula.
        //
        // Yang sudah disetujui mempertahankan nomornya kalau sudah punya (B-12:
        // tidak berubah lagi setelah diberikan) dan baru diberi nomor kalau
        // memang belum pernah punya.
        $member->nia = $awal['status'] === 'pending'
            ? null
            : ($member->nia ?? PenomoranNia::berikutnya($member->tanggal_gabung->year));

        $member->save();
    }
}
