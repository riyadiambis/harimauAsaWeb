<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Wilayah;

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
class WilayahPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->kelola($user);
    }

    public function view(User $user, Wilayah $wilayah): bool
    {
        return $this->kelola($user);
    }

    public function create(User $user): bool
    {
        return $this->kelola($user);
    }

    public function update(User $user, Wilayah $wilayah): bool
    {
        return $this->kelola($user);
    }

    /**
     * Berhak menghapus tidak berarti bisa. Wilayah yang masih punya ranting
     * tetap ditolak pagar di model (HapusIndukException) dan foreign key.
     */
    public function delete(User $user, Wilayah $wilayah): bool
    {
        return $this->kelola($user);
    }

    private function kelola(User $user): bool
    {
        return $user->is_guru_besar || $user->is_sekben;
    }
}
