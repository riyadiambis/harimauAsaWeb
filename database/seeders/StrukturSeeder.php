<?php

namespace Database\Seeders;

use App\Models\Jabatan;
use App\Models\PeriodeKepengurusan;
use App\Models\Ranting;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

/**
 * Periode kepengurusan dan bagan jabatan awal sesuai
 * docs/fitur/02-anggota-struktur.md.
 *
 * Butuh AkunUjiSeeder dan WilayahRantingSeeder sudah jalan lebih dulu.
 */
class StrukturSeeder extends Seeder
{
    // Dipasang di seeder ini sendiri, bukan hanya di DatabaseSeeder:
    // tiap seeder harus aman dijalankan sendirian untuk memulihkan
    // keadaan, dan tanpa ini pemulihan meninggalkan baris audit palsu
    // seolah ada pengurus yang mengubah data.
    use WithoutModelEvents;

    public function run(): void
    {
        $periode = PeriodeKepengurusan::updateOrCreate(
            ['nama' => 'Kepengurusan 2026-2027'],
            ['tahun_mulai' => 2026, 'tahun_selesai' => 2027, 'aktif' => true],
        );

        $melak = Ranting::where('nama', 'Melak Ulu')->first();
        $samarindaUlu = Ranting::where('nama', 'Samarinda Ulu')->first();

        // TODO: ganti dengan data pengurus resmi.
        $guruBesar = $this->jabatan($periode, 'gurubesar', 'Guru Besar', null, null, 1);

        $kutaiBarat = $this->jabatan($periode, 'editorcoba1', 'Ketua Wilayah Kutai Barat', $guruBesar, null, 1);
        $this->jabatan($periode, 'wargacoba1', 'Ketua Ranting Melak Ulu', $kutaiBarat, $melak, 1);

        $samarinda = $this->jabatan($periode, 'anggotacoba1', 'Ketua Wilayah Samarinda', $guruBesar, null, 2);
        $this->jabatan($periode, 'adminmin', 'Ketua Ranting Samarinda Ulu', $samarinda, $samarindaUlu, 1);

        $this->jabatan($periode, 'sekbenuang', 'Sekben Umum', $guruBesar, null, 3);
    }

    private function jabatan(
        PeriodeKepengurusan $periode,
        string $username,
        string $namaJabatan,
        ?Jabatan $atasan,
        ?Ranting $ranting,
        int $urutan,
    ): Jabatan {
        return Jabatan::updateOrCreate(
            ['periode_id' => $periode->id, 'nama_jabatan' => $namaJabatan],
            [
                'user_id' => User::where('username', $username)->value('id'),
                'parent_id' => $atasan?->id,
                'ranting_id' => $ranting?->id,
                'urutan' => $urutan,
            ],
        );
    }
}
