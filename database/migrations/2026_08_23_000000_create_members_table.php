<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Versi minimal: hanya kolom yang dibutuhkan alur autentikasi (status pending).
     * Kolom keanggotaan selengkapnya — nia, no_warga, tingkat_keanggotaan, tingkatan,
     * tingkatan_urutan, ranting_id, tanggal_naik_warga, iuran_override — menyusul
     * di fitur 02. Lihat docs/fitur/02-anggota-struktur.md.
     */
    public function up(): void
    {
        Schema::create('members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->enum('status', ['pending', 'aktif', 'non_aktif', 'alumni'])->default('pending');
            $table->date('tanggal_gabung');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('members');
    }
};
