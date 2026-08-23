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
        // Seeder akun uji (adminMin, guruBesar, sekbenUang, dst) menyusul bersama
        // alur registrasi & login. Lihat docs/fitur/01-auth.md bagian "Akun uji".
    }
}
