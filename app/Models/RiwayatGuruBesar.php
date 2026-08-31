<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

// B-11: diisi manual dan sengaja tidak punya relasi ke `jabatan` maupun `users`.
// Guru Besar dari masa sebelum sistem ini ada tidak punya akun.
#[Table('riwayat_guru_besar')]
#[Fillable(['nama', 'tahun_mulai', 'tahun_selesai', 'foto', 'keterangan', 'urutan'])]
class RiwayatGuruBesar extends Model
{
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tahun_mulai' => 'integer',
            'tahun_selesai' => 'integer',
        ];
    }
}
