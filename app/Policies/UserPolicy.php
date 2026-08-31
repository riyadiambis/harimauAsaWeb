<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    /**
     * B-6: memberi dan mencabut hak akses (is_editor, is_guru_besar, is_sekben,
     * is_admin) hanya boleh Admin.
     *
     * Guru Besar pun tidak bisa — dia berkuasa atas keanggotaan (B-2, B-5),
     * bukan atas hak akses aplikasi.
     */
    public function ubahHakAkses(User $user, User $target): bool
    {
        return $user->is_admin;
    }

    /**
     * B-14: pencabutan hak admin punya dua pagar tambahan di atas B-6 — tidak
     * boleh mencabut milik sendiri, dan admin terakhir tidak boleh dicabut.
     */
    public function cabutHakAdmin(User $user, User $target): bool
    {
        if (! $this->ubahHakAkses($user, $target)) {
            return false;
        }

        if ($user->getKey() === $target->getKey()) {
            return false;
        }

        return ! $target->adminTerakhir();
    }

    /**
     * A-7: reset kata sandi anggota — Guru Besar, Sekben Umum, atau Admin.
     */
    public function resetSandi(User $user, User $target): bool
    {
        return $user->is_guru_besar || $user->is_sekben || $user->is_admin;
    }
}
