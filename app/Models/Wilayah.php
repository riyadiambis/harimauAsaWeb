<?php

namespace App\Models;

use App\Exceptions\HapusIndukException;
use App\Models\Concerns\MencatatAudit;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table('wilayah')]
#[Fillable(['nama', 'urutan'])]
class Wilayah extends Model
{
    use HasFactory, MencatatAudit;

    /**
     * B-10 lewat B-16.
     *
     * @return array<int, string>
     */
    public function kolomDiaudit(): array
    {
        return ['nama', 'urutan'];
    }

    /**
     * Pembuatan dan penghapusan ikut dicatat, bukan hanya penyuntingan —
     * menghapus wilayah menghilangkan induk dari ranting-rantingnya.
     *
     * @return array<int, string>
     */
    public function peristiwaDiaudit(): array
    {
        return ['created', 'updated', 'deleted'];
    }

    /**
     * Aturan hapus induk: wilayah yang masih punya ranting tidak boleh dihapus.
     *
     * Foreign key `restrictOnDelete` sudah menolaknya di database, tapi yang
     * sampai ke pengurus berupa SQLSTATE[23000]. Pagar ini menolak lebih dulu
     * dengan kalimat yang bisa dibaca; foreign key tetap jadi jaring terakhir
     * untuk jalur yang tidak lewat Eloquent.
     */
    protected static function booted(): void
    {
        static::deleting(function (self $wilayah): void {
            $jumlah = $wilayah->ranting()->count();

            if ($jumlah > 0) {
                throw HapusIndukException::wilayahMasihPunyaRanting($wilayah->nama, $jumlah);
            }
        });
    }

    /**
     * @return HasMany<Ranting, $this>
     */
    public function ranting(): HasMany
    {
        return $this->hasMany(Ranting::class);
    }
}
