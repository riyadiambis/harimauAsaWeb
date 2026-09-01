<?php

namespace App\Policies;

use App\Models\Ranting;
use App\Models\User;

/**
 * B-16: wilayah dan ranting dikelola Guru Besar dan Sekben Umum saja.
 *
 * Admin sengaja TIDAK cukup — sama seperti B-2 dan B-4. Kalau seorang Admin
 * juga memegang salah satu bendera itu, dia lolos lewat bendera tersebut, bukan
 * lewat status adminnya.
 *
 * Editor lolos gerbang panel B-15 dan boleh masuk /admin, tapi tidak akan
 * melihat menu wilayah/ranting sama sekali.
 */
class RantingPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->kelola($user);
    }

    public function view(User $user, Ranting $ranting): bool
    {
        return $this->kelola($user);
    }

    public function create(User $user): bool
    {
        return $this->kelola($user);
    }

    public function update(User $user, Ranting $ranting): bool
    {
        return $this->kelola($user);
    }

    /**
     * Ranting boleh dihapus; `members.ranting_id` dan `jabatan.ranting_id`
     * anggotanya jadi null dan barisnya tetap ada. Karena itu penghapusannya
     * ikut ditulis ke audit log (B-10).
     */
    public function delete(User $user, Ranting $ranting): bool
    {
        return $this->kelola($user);
    }

    private function kelola(User $user): bool
    {
        return $user->is_guru_besar || $user->is_sekben;
    }
}
