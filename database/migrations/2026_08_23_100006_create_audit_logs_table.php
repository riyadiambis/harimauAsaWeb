<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Skema dari PRD §8. Dibuat di fitur 02 karena fitur ini sudah mengubah
     * sabuk, jabatan, dan status anggota — semuanya wajib tercatat (B-10).
     */
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();

            // Nullable: perubahan bisa datang dari seeder, job terjadwal, atau
            // perintah artisan yang tidak punya pengguna yang sedang masuk.
            // Baris tetap ditulis supaya jejaknya tidak hilang.
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();

            $table->string('aksi');
            $table->string('subject_type');
            $table->unsignedBigInteger('subject_id');
            $table->json('before')->nullable();
            $table->json('after')->nullable();
            $table->string('ip', 45)->nullable();

            // PRD menyebut created_at saja — baris audit tidak pernah diubah.
            $table->timestamp('created_at')->nullable();

            $table->index(['subject_type', 'subject_id']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
