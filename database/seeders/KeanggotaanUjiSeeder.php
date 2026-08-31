<?php

namespace Database\Seeders;

use App\Models\Member;
use App\Models\Ranting;
use App\Models\User;
use App\Support\PenomoranNia;
use Illuminate\Database\Seeder;

/**
 * Melengkapi data keanggotaan akun uji yang dibuat AkunUjiSeeder — nia, tingkat,
 * sabuk, dan ranting. Dipisah supaya AkunUjiSeeder tetap murni soal autentikasi
 * (fitur 01) dan seeder ini yang memegang aturan fitur 02.
 *
 * Butuh AkunUjiSeeder dan WilayahRantingSeeder sudah jalan lebih dulu.
 */
class KeanggotaanUjiSeeder extends Seeder
{
    public function run(): void
    {
        $melak = Ranting::where('nama', 'Melak Ulu')->first();
        $samarinda = Ranting::where('nama', 'Samarinda Ulu')->first();

        // username => [tingkat_keanggotaan, tingkatan, ranting]
        $keanggotaan = [
            'adminmin' => ['anggota', 'kuning', $melak],
            'gurubesar' => ['warga', 'putih_warga_3', $melak],
            'sekbenuang' => ['warga', 'merah_warga_2', $samarinda],
            'editorcoba1' => ['anggota', 'oren', $melak],
            'wargacoba1' => ['warga', 'merah_warga_1', $melak],
            'anggotacoba1' => ['anggota', 'hitam_polos', $samarinda],
            'pendingcoba1' => ['anggota', 'hitam_polos', null],
        ];

        foreach ($keanggotaan as $username => [$tingkat, $sabuk, $ranting]) {
            $user = User::where('username', $username)->first();

            if ($user === null) {
                continue;
            }

            $member = $user->member;

            $member->tingkat_keanggotaan = $tingkat;
            $member->tingkatan = $sabuk;
            $member->ranting_id = $ranting?->id;

            if ($tingkat === 'warga' && $member->tanggal_naik_warga === null) {
                $member->tanggal_naik_warga = $member->tanggal_gabung->copy()->addMonths(18);
            }

            // DatabaseSeeder memakai WithoutModelEvents, jadi hook pemberian nia
            // di model Member tidak menyala di sini — nomornya diberikan manual.
            // B-1: hanya untuk yang sudah disetujui; pendingcoba1 tetap tanpa nia.
            if ($member->status !== 'pending' && $member->nia === null) {
                $member->nia = PenomoranNia::berikutnya($member->tanggal_gabung->year);
            }

            $member->save();
        }

        $this->command?->info('  Anggota ber-NIA: '.Member::whereNotNull('nia')->count());
    }
}
