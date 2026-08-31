<?php

namespace App\Support;

use App\Models\Member;

/**
 * Nomor induk anggota: tahun bergabung + nomor urut empat digit, contoh
 * `2026-0001`. Nomor urut dihitung per tahun dan mulai lagi dari 1 tiap
 * ganti tahun.
 */
class PenomoranNia
{
    public const PANJANG_URUT = 4;

    public static function awalan(int $tahun): string
    {
        return $tahun.'-';
    }

    public static function format(int $tahun, int $urut): string
    {
        return self::awalan($tahun).str_pad((string) $urut, self::PANJANG_URUT, '0', STR_PAD_LEFT);
    }

    /**
     * Nomor berikutnya yang belum dipakai di tahun tersebut.
     *
     * Diurutkan sebagai string, dan itu aman selama lebar digitnya tetap —
     * "2026-0010" > "2026-0009". Kalau suatu saat tembus 9999, lebarnya
     * berubah dan pengurutan ini perlu ditinjau ulang.
     */
    public static function berikutnya(int $tahun): string
    {
        $awalan = self::awalan($tahun);

        $terakhir = Member::query()
            ->where('nia', 'like', $awalan.'%')
            ->orderByDesc('nia')
            ->value('nia');

        $urut = $terakhir === null
            ? 1
            : ((int) substr($terakhir, strlen($awalan))) + 1;

        return self::format($tahun, $urut);
    }
}
