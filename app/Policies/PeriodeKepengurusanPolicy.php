<?php

namespace App\Policies;

use App\Models\PeriodeKepengurusan;
use App\Models\User;

/**
 * B-4. Periode adalah wadah jabatan — `jabatan.periode_id` menunjuk ke sini, dan
 * B-8 serta B-9 adalah aturan tentangnya — jadi hak kelolanya mengikuti jabatan,
 * bukan berdiri sendiri. Sejalan dengan B-16 untuk wilayah/ranting.
 */
class PeriodeKepengurusanPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->kelola($user);
    }

    public function view(User $user, PeriodeKepengurusan $periode): bool
    {
        return $this->kelola($user);
    }

    public function create(User $user): bool
    {
        return $this->kelola($user);
    }

    public function update(User $user, PeriodeKepengurusan $periode): bool
    {
        return $this->kelola($user);
    }

    /**
     * Berhak menghapus tidak berarti bisa: periode yang masih punya jabatan
     * ditolak pagar di model dan foreign key.
     */
    public function delete(User $user, PeriodeKepengurusan $periode): bool
    {
        return $this->kelola($user);
    }

    private function kelola(User $user): bool
    {
        return $user->is_guru_besar || $user->is_sekben;
    }
}
