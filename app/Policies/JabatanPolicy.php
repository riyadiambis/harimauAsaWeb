<?php

namespace App\Policies;

use App\Models\Jabatan;
use App\Models\User;

/**
 * B-4: yang berhak mengisi/mengubah jabatan hanya Guru Besar dan Sekben Umum.
 */
class JabatanPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->create($user);
    }

    public function view(User $user, Jabatan $jabatan): bool
    {
        return $this->create($user);
    }

    public function create(User $user): bool
    {
        return $user->is_guru_besar || $user->is_sekben;
    }

    public function update(User $user, Jabatan $jabatan): bool
    {
        return $this->create($user);
    }

    public function delete(User $user, Jabatan $jabatan): bool
    {
        return $this->create($user);
    }
}
