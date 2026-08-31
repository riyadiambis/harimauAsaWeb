<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('periode_kepengurusan', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->unsignedSmallInteger('tahun_mulai');
            $table->unsignedSmallInteger('tahun_selesai');
            // B-8 (hanya satu yang aktif) dijaga di model, bukan di sini — MySQL
            // tidak punya partial unique index untuk "unique hanya saat true".
            $table->boolean('aktif')->default(false);
            $table->timestamps();

            $table->index('aktif');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('periode_kepengurusan');
    }
};
