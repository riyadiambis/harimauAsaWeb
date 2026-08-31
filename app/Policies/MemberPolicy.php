<?php

namespace App\Policies;

use App\Models\Member;
use App\Models\User;

class MemberPolicy
{
    /**
     * B-2: mengubah tingkat keanggotaan, sabuk, dan no warga.
     *
     * Admin sengaja TIDAK cukup. Kalau seorang Admin juga memegang salah satu
     * bendera Guru Besar / Sekben, dia lolos lewat bendera itu — bukan lewat
     * status adminnya.
     */
    public function ubahTingkatDanSabuk(User $user): bool
    {
        return $user->is_guru_besar || $user->is_sekben;
    }

    /**
     * B-13: `no_warga` disalin sendiri oleh anggota dari kartu tanda warga
     * fisiknya, lewat halaman profil. Guru Besar dan Sekben tetap bisa
     * mengisinya juga — B-2 tidak dicabut, ini menambah pemiliknya sendiri.
     *
     * Hanya relevan untuk tingkat `warga`; sebelum naik, nomornya belum ada
     * (B-1).
     */
    public function isiNoWarga(User $user, Member $member): bool
    {
        if ($member->tingkat_keanggotaan !== 'warga') {
            return false;
        }

        return $user->getKey() === $member->user_id
            || $this->ubahTingkatDanSabuk($user);
    }

    /**
     * B-5: mengubah status anggota.
     */
    public function ubahStatus(User $user): bool
    {
        return $user->is_guru_besar || $user->is_sekben;
    }

    /**
     * B-5: menyetujui pendaftar. Hanya masuk akal untuk yang masih pending.
     */
    public function setujui(User $user, Member $member): bool
    {
        return $this->ubahStatus($user) && $member->status === 'pending';
    }
}
