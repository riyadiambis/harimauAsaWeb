<?php

namespace Tests\Feature\Keanggotaan;

use App\Models\Member;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

/**
 * B-13: no_warga disalin manual dari kartu tanda warga fisik — tepat 8 digit,
 * unik, diisi anggota sendiri.
 */
class NoWargaTest extends TestCase
{
    use RefreshDatabase;

    private function lolos(?string $nilai, ?int $abaikanId = null): bool
    {
        return Validator::make(
            ['no_warga' => $nilai],
            ['no_warga' => Member::aturanNoWarga($abaikanId)],
        )->passes();
    }

    #[TestWith(['12345678'])]
    #[TestWith(['00000001'])]
    #[TestWith(['99999999'])]
    public function test_delapan_digit_diterima(string $nilai): void
    {
        $this->assertTrue($this->lolos($nilai), $nilai);
    }

    #[TestWith(['1234567'])] // 7 digit
    #[TestWith(['123456789'])] // 9 digit
    #[TestWith(['1234 678'])] // ada spasi
    #[TestWith(['1234-678'])] // ada tanda hubung
    #[TestWith(['+1234567'])] // tanda plus, panjangnya 8
    #[TestWith(['1234567a'])] // ada huruf
    #[TestWith(['1.234567'])] // titik desimal
    public function test_selain_delapan_digit_angka_ditolak(string $nilai): void
    {
        $this->assertFalse($this->lolos($nilai), $nilai);
    }

    /** Kosong tetap boleh — sebelum naik warga nomornya memang belum ada (B-1). */
    public function test_null_diterima(): void
    {
        $this->assertTrue($this->lolos(null));
    }

    public function test_nomor_yang_sudah_dipakai_ditolak(): void
    {
        Member::factory()->create(['no_warga' => '12345678']);

        $this->assertFalse($this->lolos('12345678'));
    }

    public function test_nomor_sendiri_tidak_dianggap_bentrok_saat_menyunting(): void
    {
        $member = Member::factory()->create(['no_warga' => '12345678']);

        $this->assertTrue($this->lolos('12345678', $member->id));
    }

    public function test_unik_dijaga_database(): void
    {
        Member::factory()->create(['no_warga' => '12345678']);

        $this->expectException(UniqueConstraintViolationException::class);

        Member::factory()->create(['no_warga' => '12345678']);
    }

    // --- Siapa yang boleh mengisi -------------------------------------------

    public function test_anggota_boleh_mengisi_no_warga_miliknya_sendiri(): void
    {
        $member = Member::factory()->warga()->create();

        $this->assertTrue($member->user->can('isiNoWarga', $member));
    }

    public function test_anggota_tidak_boleh_mengisi_milik_orang_lain(): void
    {
        $member = Member::factory()->warga()->create();
        $orangLain = User::factory()->create();

        $this->assertFalse($orangLain->can('isiNoWarga', $member));
    }

    /** B-2 tidak dicabut: pengurus tetap bisa mengisikannya. */
    public function test_guru_besar_dan_sekben_tetap_boleh_mengisi(): void
    {
        $member = Member::factory()->warga()->create();

        foreach (['is_guru_besar', 'is_sekben'] as $bendera) {
            $pengurus = User::factory()->create();
            $pengurus->{$bendera} = true;
            $pengurus->save();

            $this->assertTrue($pengurus->can('isiNoWarga', $member), $bendera);
        }
    }

    /** B-1: sebelum naik warga, nomor ini belum berlaku. */
    public function test_tingkat_anggota_belum_boleh_punya_no_warga(): void
    {
        $member = Member::factory()->create(['tingkat_keanggotaan' => 'anggota']);

        $this->assertFalse($member->user->can('isiNoWarga', $member));
    }

    // Lebar kolom varchar(8) hanya berlaku di MySQL. SQLite — yang dipakai test
    // suite — mengabaikan panjang VARCHAR sepenuhnya, jadi menguji lebar kolom
    // di sini akan selalu lolos tanpa membuktikan apa pun. Aturan 8 digit yang
    // sesungguhnya dijaga validasi di atas.
}
