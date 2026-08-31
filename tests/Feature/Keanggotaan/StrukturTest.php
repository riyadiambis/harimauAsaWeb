<?php

namespace Tests\Feature\Keanggotaan;

use App\Models\Jabatan;
use App\Models\PeriodeKepengurusan;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StrukturTest extends TestCase
{
    use RefreshDatabase;

    /** Skenario 6. */
    public function test_menandai_periode_baru_aktif_menonaktifkan_yang_lama(): void
    {
        $lama = PeriodeKepengurusan::factory()->aktif()->create();
        $baru = PeriodeKepengurusan::factory()->aktif()->create();

        $this->assertFalse($lama->fresh()->aktif);
        $this->assertTrue($baru->fresh()->aktif);
        $this->assertSame(1, PeriodeKepengurusan::aktif()->count());
    }

    /** B-9: periode lama jadi arsip, bukan dihapus. */
    public function test_periode_lama_tetap_ada_setelah_dinonaktifkan(): void
    {
        $lama = PeriodeKepengurusan::factory()->aktif()->create();
        PeriodeKepengurusan::factory()->aktif()->create();

        $this->assertDatabaseHas('periode_kepengurusan', ['id' => $lama->id]);
    }

    public function test_mengaktifkan_ulang_periode_lama_menonaktifkan_yang_baru(): void
    {
        $lama = PeriodeKepengurusan::factory()->aktif()->create();
        $baru = PeriodeKepengurusan::factory()->aktif()->create();

        // Dimuat ulang dulu: hook B-8 menonaktifkan yang lama lewat query
        // builder, jadi instance $lama di memori masih menyimpan aktif = true.
        // Panel pengelola selalu memuat baris segar sebelum menyimpannya.
        $lama->refresh()->update(['aktif' => true]);

        $this->assertTrue($lama->fresh()->aktif);
        $this->assertFalse($baru->fresh()->aktif);
        $this->assertSame(1, PeriodeKepengurusan::aktif()->count());
    }

    /** Skenario 7. */
    public function test_bagan_terbentuk_sampai_tiga_tingkat(): void
    {
        $periode = PeriodeKepengurusan::factory()->aktif()->create();

        $guruBesar = Jabatan::factory()->create([
            'periode_id' => $periode->id,
            'nama_jabatan' => 'Guru Besar',
        ]);
        $wilayah = Jabatan::factory()->dibawah($guruBesar)->create([
            'nama_jabatan' => 'Ketua Wilayah Kutai Barat',
        ]);
        $ranting = Jabatan::factory()->dibawah($wilayah)->create([
            'nama_jabatan' => 'Ketua Ranting Melak Ulu',
        ]);

        $this->assertNull($guruBesar->parent);
        $this->assertSame('Guru Besar', $ranting->parent->parent->nama_jabatan);
        $this->assertSame(1, $guruBesar->children()->count());
        $this->assertSame(3, $periode->jabatan()->count());
    }

    /** Skenario 8: B-3, nama jabatan teks bebas. */
    public function test_nama_jabatan_menerima_teks_apa_pun(): void
    {
        $jabatan = Jabatan::factory()->create(['nama_jabatan' => 'Humas Ranting Melak']);

        $this->assertSame('Humas Ranting Melak', $jabatan->fresh()->nama_jabatan);
    }

    /** Jabatan induk dihapus → bawahannya naik jadi akar, tidak ikut hilang. */
    public function test_menghapus_jabatan_induk_menaikkan_bawahannya(): void
    {
        $induk = Jabatan::factory()->create();
        $anak = Jabatan::factory()->dibawah($induk)->create();

        $induk->delete();

        $this->assertNotNull($anak->fresh());
        $this->assertNull($anak->fresh()->parent_id);
    }

    /** B-9 dijaga foreign key: periode yang masih punya jabatan tidak bisa dihapus. */
    public function test_periode_yang_masih_punya_jabatan_tidak_bisa_dihapus(): void
    {
        $periode = PeriodeKepengurusan::factory()->create();
        Jabatan::factory()->create(['periode_id' => $periode->id]);

        $this->expectException(QueryException::class);

        $periode->delete();
    }
}
