<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Melengkapi `members` yang sudah dibuat versi minimalnya di fitur 01
     * (user_id, status, tanggal_gabung). Tabelnya TIDAK dibuat ulang.
     */
    public function up(): void
    {
        Schema::table('members', function (Blueprint $table) {
            // nia null selama akun masih pending — nomor induk baru diberikan
            // saat pendaftaran disetujui (B-1). MySQL mengizinkan banyak NULL
            // di bawah unique index, jadi unique tetap berlaku.
            $table->string('nia')->nullable()->unique()->after('user_id');

            // Hanya terisi untuk tingkat warga (B-1).
            $table->string('no_warga')->nullable()->unique()->after('nia');

            $table->enum('tingkat_keanggotaan', ['anggota', 'warga'])
                ->default('anggota')
                ->after('no_warga');

            $table->enum('tingkatan', [
                'hitam_polos',
                'kuning',
                'oren',
                'merah_warga_1',
                'merah_warga_2',
                'putih_warga_3',
            ])->default('hitam_polos')->after('tingkat_keanggotaan');

            // Diisi otomatis oleh mutator di model Member, jangan diketik manual.
            $table->unsignedTinyInteger('tingkatan_urutan')->default(1)->after('tingkatan');

            // Ranting dihapus → anggotanya tetap ada, hanya kehilangan ranting.
            $table->foreignId('ranting_id')->nullable()->after('tingkatan_urutan')
                ->constrained('ranting')->nullOnDelete();

            $table->date('tanggal_naik_warga')->nullable()->after('tanggal_gabung');

            // Nominal iuran khusus untuk anggota ini. Rupiah, tidak pernah negatif.
            $table->unsignedInteger('iuran_override')->nullable()->after('tanggal_naik_warga');

            // Job penerbitan tagihan fitur 03 menanyakan persis dua kolom ini.
            $table->index(['tingkat_keanggotaan', 'status'], 'members_penerbitan_index');

            // Pengurutan sabuk tertinggi → terendah (skenario uji 2).
            $table->index('tingkatan_urutan');
        });
    }

    public function down(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->dropForeign(['ranting_id']);
            $table->dropIndex('members_penerbitan_index');
            $table->dropIndex(['tingkatan_urutan']);

            $table->dropColumn([
                'nia',
                'no_warga',
                'tingkat_keanggotaan',
                'tingkatan',
                'tingkatan_urutan',
                'ranting_id',
                'tanggal_naik_warga',
                'iuran_override',
            ]);
        });
    }
};
