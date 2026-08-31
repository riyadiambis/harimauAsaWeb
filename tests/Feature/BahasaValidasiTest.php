<?php

namespace Tests\Feature;

use App\Models\Member;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

/**
 * Bahasa antarmuka aplikasi ini Indonesia, termasuk pesan validasi bawaan
 * Laravel. Sebelum ada `lang/id/`, `no_warga` yang salah format menghasilkan
 * "The no warga has already been taken." di form yang seluruhnya berbahasa
 * Indonesia.
 */
class BahasaValidasiTest extends TestCase
{
    public function test_locale_id_dengan_fallback_en(): void
    {
        $this->assertSame('id', config('app.locale'));
        $this->assertSame('en', config('app.fallback_locale'));
    }

    public function test_berkas_terjemahan_id_terpasang(): void
    {
        foreach (['validation', 'auth', 'passwords', 'pagination'] as $berkas) {
            $this->assertFileExists(lang_path("id/{$berkas}.php"), $berkas);
        }
    }

    /**
     * Setiap kunci bahasa Inggris punya padanan di `id`. Kalau Laravel menambah
     * aturan validasi baru saat upgrade, tes ini yang memberi tahu — bukan
     * pengguna yang menemukan kalimat Inggris di layar.
     */
    public function test_seluruh_kunci_validasi_sudah_diterjemahkan(): void
    {
        $ratakan = function (array $a, string $awalan = '') use (&$ratakan): array {
            $keluar = [];

            foreach ($a as $kunci => $nilai) {
                if (in_array($kunci, ['custom', 'attributes'], true)) {
                    continue;
                }

                is_array($nilai)
                    ? $keluar = array_merge($keluar, $ratakan($nilai, "{$awalan}{$kunci}."))
                    : $keluar[] = $awalan.$kunci;
            }

            return $keluar;
        };

        $en = $ratakan(require lang_path('en/validation.php'));
        $id = $ratakan(require lang_path('id/validation.php'));

        $this->assertSame([], array_values(array_diff($en, $id)),
            'Kunci validasi yang belum diterjemahkan ke bahasa Indonesia');
    }

    public function test_pesan_no_warga_berbahasa_indonesia(): void
    {
        $v = Validator::make(
            ['no_warga' => '1234567'],
            ['no_warga' => Member::aturanNoWarga()],
        );

        $pesan = $v->errors()->first('no_warga');

        $this->assertStringContainsString('Nomor warga', $pesan);
        $this->assertStringNotContainsString('no warga', $pesan);
        $this->assertStringNotContainsString('must', $pesan);
    }

    /**
     * Nama kolom mentah — "no warga", "tingkat keanggotaan" — tidak boleh bocor
     * ke layar. Ini yang dijaga daftar `attributes`.
     */
    public function test_nama_atribut_manusiawi(): void
    {
        $harapan = [
            'no_warga' => 'Nomor warga',
            'nia' => 'NIA',
            'tingkat_keanggotaan' => 'Tingkat keanggotaan',
            'tingkatan' => 'Tingkatan sabuk',
            'username' => 'Username',
            'nama' => 'Nama lengkap',
            'password' => 'Kata sandi',
        ];

        foreach ($harapan as $kolom => $label) {
            $v = Validator::make([$kolom => null], [$kolom => ['required']]);

            $this->assertSame(
                "{$label} wajib diisi.",
                $v->errors()->first($kolom),
                "nama manusiawi untuk `{$kolom}`",
            );
        }
    }
}
