<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * A-7: sandi sementara acak yang dibuat pengurus saat mereset kata sandi
 * anggota, ditampilkan sekali di layar untuk disampaikan lewat WhatsApp.
 */
class SandiSementara
{
    public const PANJANG = 10;

    /**
     * Huruf dan angka yang mudah dibedakan saat dibacakan atau diketik ulang.
     *
     * Sengaja tanpa 0/O, 1/l/I, dan tanpa simbol: sandi ini disalin manusia dari
     * pesan WhatsApp ke kolom kata sandi, dan tiap karakter yang bisa tertukar
     * berujung pada "sandinya tidak bisa dipakai" yang sebenarnya salah ketik.
     *
     * A-3 hanya mensyaratkan minimal 8 karakter tanpa kombinasi simbol, jadi
     * membuang karakter ambigu tidak melanggar apa pun.
     */
    private const ABJAD = 'abcdefghjkmnpqrstuvwxyzACDEFGHJKLMNPQRSTUVWXYZ23456789';

    public static function buat(): string
    {
        $panjang = strlen(self::ABJAD);

        return collect(range(1, self::PANJANG))
            ->map(fn (): string => self::ABJAD[random_int(0, $panjang - 1)])
            ->implode('');
    }

    /**
     * Dipakai tes untuk memastikan yang dihasilkan memang memenuhi A-3 dan
     * tidak memuat karakter ambigu.
     */
    public static function sahih(string $sandi): bool
    {
        return strlen($sandi) >= 8
            && Str::of($sandi)->replaceMatches('/['.preg_quote(self::ABJAD, '/').']/', '')->isEmpty();
    }
}
