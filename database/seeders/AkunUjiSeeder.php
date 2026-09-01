<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AkunUjiSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Keadaan awal seluruh akun uji — SATU sumber kebenaran.
     *
     * KeanggotaanUjiSeeder membaca daftar yang sama untuk kolom fitur 02, supaya
     * kedua seeder tidak pernah berbeda pendapat soal siapa saja akun ujinya.
     * Sebelumnya daftar username-nya diketik dua kali di dua berkas.
     *
     * Kolom fitur 01 (nama, sandi, hak akses, status, tanggal_gabung) dipulihkan
     * seeder ini; kolom fitur 02 (tingkat, sabuk, ranting, nia, no_warga,
     * tanggal_naik_warga) oleh KeanggotaanUjiSeeder.
     *
     * TODO: ganti dengan data pengurus resmi.
     *
     * @var array<string, array<string, mixed>>
     */
    public const DAFTAR = [
        'adminmin' => [
            'nama' => 'Bayu Prasetyo',
            'sandi' => 'adminamin123',
            'hak_akses' => ['is_admin' => true],
            'status' => 'aktif',
            'umur_bulan' => 48,
            'tingkat_keanggotaan' => 'anggota',
            'tingkatan' => 'kuning',
            'ranting' => 'Melak Ulu',
        ],
        'gurubesar' => [
            'nama' => 'Suryadi Hasan',
            'sandi' => 'gurusuhu212',
            'hak_akses' => ['is_guru_besar' => true],
            'status' => 'aktif',
            'umur_bulan' => 96,
            'tingkat_keanggotaan' => 'warga',
            'tingkatan' => 'putih_warga_3',
            'ranting' => 'Melak Ulu',
        ],
        'sekbenuang' => [
            'nama' => 'Nurhaliza Putri',
            'sandi' => 'uangUang123',
            'hak_akses' => ['is_sekben' => true],
            'status' => 'aktif',
            'umur_bulan' => 60,
            'tingkat_keanggotaan' => 'warga',
            'tingkatan' => 'merah_warga_2',
            'ranting' => 'Samarinda Ulu',
        ],
        'editorcoba1' => [
            'nama' => 'Dimas Anggara',
            'sandi' => 'editedit1',
            'hak_akses' => ['is_editor' => true],
            'status' => 'aktif',
            'umur_bulan' => 30,
            'tingkat_keanggotaan' => 'anggota',
            'tingkatan' => 'oren',
            'ranting' => 'Melak Ulu',
        ],
        'wargacoba1' => [
            'nama' => 'Fikri Ramadhan',
            'sandi' => 'wargawarga1',
            'hak_akses' => [],
            'status' => 'aktif',
            'umur_bulan' => 36,
            'tingkat_keanggotaan' => 'warga',
            'tingkatan' => 'merah_warga_1',
            'ranting' => 'Melak Ulu',
        ],
        'anggotacoba1' => [
            'nama' => 'Alif Nugroho',
            'sandi' => 'anggotaanggota1',
            'hak_akses' => [],
            'status' => 'aktif',
            'umur_bulan' => 8,
            'tingkat_keanggotaan' => 'anggota',
            'tingkatan' => 'hitam_polos',
            'ranting' => 'Samarinda Ulu',
        ],
        // Sengaja dibiarkan pending untuk menguji A-6. Jangan disetujui.
        'pendingcoba1' => [
            'nama' => 'Rangga Saputra',
            'sandi' => 'pendingpending1',
            'hak_akses' => [],
            'status' => 'pending',
            'umur_bulan' => 0,
            'tingkat_keanggotaan' => 'anggota',
            'tingkatan' => 'hitam_polos',
            'ranting' => null,
        ],
    ];

    /**
     * Akun uji sesuai docs/fitur/01-auth.md.
     *
     * Sandi di daftar atas HANYA untuk pengembangan — ganti sebelum dipakai
     * sungguhan.
     *
     * WithoutModelEvents dipasang di seeder ini sendiri, bukan hanya di
     * DatabaseSeeder: seeder ini harus aman dijalankan sendirian untuk memulihkan
     * keadaan, dan tanpa itu pemulihan meninggalkan baris audit palsu seolah ada
     * pengurus yang mengubah data.
     */
    public function run(): void
    {
        foreach (self::DAFTAR as $username => $awal) {
            $this->pulihkan($username, $awal);
        }
    }

    /**
     * @param  array<string, mixed>  $awal
     */
    private function pulihkan(string $username, array $awal): void
    {
        // forceFill: kolom hak akses sengaja di luar #[Fillable] di model, dan
        // firstOrNew bikin seeder ini aman dijalankan ulang tanpa migrate:fresh.
        $user = User::withTrashed()->firstOrNew(['username' => $username]);

        $user->forceFill([
            'nama' => $awal['nama'],
            'password' => $awal['sandi'],
            'is_editor' => false,
            'is_guru_besar' => false,
            'is_sekben' => false,
            'is_admin' => false,
            // Dikembalikan false: akun uji yang sandinya sempat direset lewat
            // panel (A-7) akan terkunci di halaman ganti sandi selamanya kalau
            // benderanya tidak ikut dipulihkan.
            'harus_ganti_sandi' => false,
            'deleted_at' => null,
            ...$awal['hak_akses'],
        ])->save();

        $user->member()->updateOrCreate([], [
            'status' => $awal['status'],
            'tanggal_gabung' => now()->subMonths($awal['umur_bulan'])->startOfMonth(),
        ]);
    }
}
