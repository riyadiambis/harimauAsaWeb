<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * `no_warga` selalu 8 digit angka dan disalin dari kartu tanda warga fisik.
     * Lebarnya dipatok di kolom supaya batas itu ikut terbaca dari skema, bukan
     * hanya dari aturan validasi. Unique index yang sudah ada tidak disentuh.
     */
    public function up(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->string('no_warga', 8)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->string('no_warga')->nullable()->change();
        });
    }
};
