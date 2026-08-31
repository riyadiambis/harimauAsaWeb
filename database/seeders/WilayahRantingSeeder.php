<?php

namespace Database\Seeders;

use App\Models\Ranting;
use App\Models\Wilayah;
use Illuminate\Database\Seeder;

class WilayahRantingSeeder extends Seeder
{
    /**
     * Data awal sesuai docs/fitur/02-anggota-struktur.md.
     */
    public function run(): void
    {
        $data = [
            'Kutai Barat' => ['Melak Ulu'],
            'Samarinda' => ['Samarinda Ulu'],
        ];

        $urutanWilayah = 1;

        foreach ($data as $namaWilayah => $daftarRanting) {
            $wilayah = Wilayah::firstOrNew(['nama' => $namaWilayah]);
            $wilayah->urutan = $urutanWilayah++;
            $wilayah->save();

            $urutanRanting = 1;

            foreach ($daftarRanting as $namaRanting) {
                Ranting::updateOrCreate(
                    ['wilayah_id' => $wilayah->id, 'nama' => $namaRanting],
                    ['urutan' => $urutanRanting++],
                );
            }
        }
    }
}
