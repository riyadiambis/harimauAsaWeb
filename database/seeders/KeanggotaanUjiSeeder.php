<?php

namespace Database\Seeders;

use App\Models\Member;
use App\Models\Ranting;
use App\Models\User;
use App\Support\PenomoranNia;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

/**
 * Kolom keanggotaan (fitur 02) untuk akun uji. Butuh AkunUjiSeeder dan
 * WilayahRantingSeeder lebih dulu.
 *
 * MEMULIHKAN, bukan melengkapi: kolom yang awalnya null dikembalikan ke null.
 * Kalau hanya melengkapi yang kosong, akun yang terlanjur diubah lewat panel
 * tidak pernah benar-benar kembali dan seeder ini cuma tampak idempoten.
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

            // Pendaftaran sungguhan aman: loop hanya berjalan untuk username
            // yang ada di DAFTAR.
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

        // B-13: tidak ada akun uji yang punya nomor kartu warga.
        $member->no_warga = null;

        // B-7. Dipaksa, bukan dipertahankan — tanggal basi akan dibaca
        // penerbitan tagihan fitur 03 kalau orangnya naik warga lagi.
        $member->tanggal_naik_warga = $awal['tingkat_keanggotaan'] === 'warga'
            ? $member->tanggal_gabung->copy()->addMonths(18)
            : null;

        // B-1: pending tidak punya nia, jadi nomor yang terlanjur terbit dicabut.
        // B-12: yang sudah sah dipertahankan, tidak diacak ulang tiap seeding.
        $member->nia = $awal['status'] === 'pending'
            ? null
            : ($member->nia ?? PenomoranNia::berikutnya($member->tanggal_gabung->year));

        $member->save();
    }
}
