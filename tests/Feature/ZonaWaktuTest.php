<?php

namespace Tests\Feature;

use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * BR-16: zona waktu aplikasi Asia/Makassar (WITA, UTC+8), bukan UTC.
 *
 * Sengaja TIDAK memasang APP_TIMEZONE di `.env.testing` — dengan begitu tes ini
 * benar-benar memeriksa nilai bawaan di `config/app.php`. Kalau dipin di env
 * uji, tes tetap hijau walau konfigurasinya diam-diam kembali ke UTC.
 */
class ZonaWaktuTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_zona_waktu_aplikasi_asia_makassar(): void
    {
        $this->assertSame('Asia/Makassar', config('app.timezone'));
    }

    /** Laravel menyetel zona PHP dari config; ini memastikan itu benar terjadi. */
    public function test_zona_waktu_php_ikut_tersetel(): void
    {
        $this->assertSame('Asia/Makassar', date_default_timezone_get());
    }

    public function test_carbon_now_memakai_wita_bukan_utc(): void
    {
        $sekarang = Carbon::now();

        $this->assertSame('Asia/Makassar', $sekarang->getTimezone()->getName());
        $this->assertNotSame('UTC', $sekarang->getTimezone()->getName());
    }

    /** WITA = UTC+8. 28800 detik, tanpa DST sepanjang tahun. */
    public function test_selisihnya_delapan_jam_di_depan_utc(): void
    {
        $this->assertSame(8 * 3600, Carbon::now()->getOffset());

        $wita = Carbon::now();
        $utc = Carbon::now('UTC');

        $this->assertSame(
            $utc->copy()->addHours(8)->format('Y-m-d H:i'),
            $wita->format('Y-m-d H:i'),
        );
    }

    /**
     * Yang sesungguhnya dijaga aturan ini: batas hari. Pukul 02:00 WITA masih
     * hari sebelumnya kalau dibaca sebagai UTC — dan itu menggeser tanggal
     * penerbitan tagihan (fitur 03) serta jendela bebas denda tanggal 1-5
     * (fitur 04) ke hari yang salah.
     */
    public function test_batas_hari_dihitung_menurut_wita(): void
    {
        // 1 Januari 2026 pukul 02:00 WITA = 31 Desember 2025 pukul 18:00 UTC.
        Carbon::setTestNow(Carbon::parse('2026-01-01 02:00:00', 'Asia/Makassar'));

        $this->assertSame('2026-01-01', Carbon::now()->toDateString());
        $this->assertSame(1, Carbon::now()->day);

        // Pembanding: pembacaan UTC atas instan yang sama jatuh di hari — dan
        // tahun — yang berbeda.
        $this->assertSame('2025-12-31', Carbon::now()->copy()->utc()->toDateString());
    }

    /**
     * B-12 menurunkan tahun `nia` dari `now()->year`. Salah zona di pergantian
     * tahun berarti pendaftar dini hari 1 Januari mendapat nomor tahun lalu.
     */
    public function test_tahun_nia_ikut_wita_di_pergantian_tahun(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-01-01 07:30:00', 'Asia/Makassar'));

        $this->assertSame(2026, Carbon::now()->year);
        $this->assertSame(2025, Carbon::now()->copy()->utc()->year);
    }
}
