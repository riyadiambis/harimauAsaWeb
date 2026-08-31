<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Urutannya penting: keanggotaan dan struktur menempel pada akun uji
        // dan pada wilayah/ranting yang sudah ada.
        $this->call([
            AkunUjiSeeder::class,
            WilayahRantingSeeder::class,
            KeanggotaanUjiSeeder::class,
            StrukturSeeder::class,
        ]);
    }
}
