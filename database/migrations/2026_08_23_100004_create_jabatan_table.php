<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jabatan', function (Blueprint $table) {
            $table->id();

            // Periode lama jadi arsip dan tidak dihapus (B-9), jadi restrict.
            $table->foreignId('periode_id')->constrained('periode_kepengurusan')->restrictOnDelete();

            // users dipakai soft delete, jadi cascade ini praktis hanya menyala
            // saat akun benar-benar dihapus permanen.
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // B-3: teks bebas. TIDAK divalidasi terhadap daftar apa pun.
            $table->string('nama_jabatan');

            // Atasan di bagan. Jabatan induk dihapus → anaknya naik jadi akar.
            $table->foreignId('parent_id')->nullable()->constrained('jabatan')->nullOnDelete();

            $table->foreignId('ranting_id')->nullable()->constrained('ranting')->nullOnDelete();

            $table->integer('urutan')->default(0);
            $table->timestamps();

            // Menyusun bagan: ambil per periode, per atasan, terurut.
            $table->index(['periode_id', 'parent_id', 'urutan']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jabatan');
    }
};
