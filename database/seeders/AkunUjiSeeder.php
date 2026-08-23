<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AkunUjiSeeder extends Seeder
{
    /**
     * Akun uji sesuai docs/fitur/01-auth.md.
     *
     * Sandi di bawah HANYA untuk pengembangan — ganti sebelum dipakai sungguhan.
     *
     * Catatan: perbedaan Warga vs Anggota ada di members.tingkat_keanggotaan yang
     * baru dibuat di fitur 02, jadi untuk sekarang kedua akun itu hanya berbeda
     * nama. Hak akses (is_*) sudah berlaku penuh.
     */
    public function run(): void
    {
        // TODO: ganti dengan data pengurus resmi.
        $akun = [
            ['Bayu Prasetyo', 'adminmin', 'adminamin123', ['is_admin' => true], 'aktif', 48],
            ['Suryadi Hasan', 'gurubesar', 'gurusuhu212', ['is_guru_besar' => true], 'aktif', 96],
            ['Nurhaliza Putri', 'sekbenuang', 'uangUang123', ['is_sekben' => true], 'aktif', 60],
            ['Dimas Anggara', 'editorcoba1', 'editedit1', ['is_editor' => true], 'aktif', 30],
            ['Fikri Ramadhan', 'wargacoba1', 'wargawarga1', [], 'aktif', 36],
            ['Alif Nugroho', 'anggotacoba1', 'anggotaanggota1', [], 'aktif', 8],
            ['Rangga Saputra', 'pendingcoba1', 'pendingpending1', [], 'pending', 0],
        ];

        foreach ($akun as [$nama, $username, $sandi, $hakAkses, $status, $umurBulan]) {
            $this->buat($nama, $username, $sandi, $hakAkses, $status, $umurBulan);
        }
    }

    /**
     * @param  array<string, bool>  $hakAkses
     */
    private function buat(
        string $nama,
        string $username,
        string $sandi,
        array $hakAkses,
        string $status,
        int $umurBulan,
    ): void {
        // forceFill: kolom hak akses sengaja di luar #[Fillable] di model, dan
        // firstOrNew bikin seeder ini aman dijalankan ulang tanpa migrate:fresh.
        $user = User::withTrashed()->firstOrNew(['username' => $username]);

        $user->forceFill([
            'nama' => $nama,
            'password' => $sandi,
            'is_editor' => false,
            'is_guru_besar' => false,
            'is_sekben' => false,
            'is_admin' => false,
            'harus_ganti_sandi' => false,
            ...$hakAkses,
        ])->save();

        $user->member()->updateOrCreate([], [
            'status' => $status,
            'tanggal_gabung' => now()->subMonths($umurBulan)->startOfMonth(),
        ]);
    }
}
