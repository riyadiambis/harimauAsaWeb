<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * B-11: sengaja terpisah dari `jabatan` dan diisi manual. Guru Besar dari
     * masa sebelum sistem ini ada tidak punya akun, jadi tidak bisa ditarik
     * otomatis dari tabel jabatan.
     */
    public function up(): void
    {
        Schema::create('riwayat_guru_besar', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->unsignedSmallInteger('tahun_mulai');
            // Null berarti masih menjabat.
            $table->unsignedSmallInteger('tahun_selesai')->nullable();
            $table->string('foto')->nullable();
            $table->text('keterangan')->nullable();
            $table->integer('urutan')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('riwayat_guru_besar');
    }
};
