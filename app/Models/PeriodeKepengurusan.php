<?php

namespace App\Models;

use App\Exceptions\HapusIndukException;
use App\Models\Concerns\MencatatAudit;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table('periode_kepengurusan')]
#[Fillable(['nama', 'tahun_mulai', 'tahun_selesai', 'aktif'])]
class PeriodeKepengurusan extends Model
{
    use HasFactory, MencatatAudit;

    /**
     * B-10 lewat B-4: periode adalah wadah jabatan, jadi perubahannya ikut
     * dicatat. Pembuatan dan penghapusan ikut karena B-8 dan B-9 membuat
     * "kapan periode ini dibuat dan dinonaktifkan" jadi bagian arsipnya.
     *
     * @return array<int, string>
     */
    public function kolomDiaudit(): array
    {
        return ['nama', 'tahun_mulai', 'tahun_selesai', 'aktif'];
    }

    /**
     * @return array<int, string>
     */
    public function peristiwaDiaudit(): array
    {
        return ['created', 'updated', 'deleted'];
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tahun_mulai' => 'integer',
            'tahun_selesai' => 'integer',
            'aktif' => 'boolean',
        ];
    }

    /**
     * B-8: hanya boleh ada satu periode aktif. Dipasang di event `saved` dan
     * bukan di satu method khusus, supaya berlaku lewat jalur mana pun —
     * panel Filament, seeder, maupun tinker.
     *
     * Penonaktifan memakai query builder (tanpa event model), jadi tidak
     * memicu ulang hook ini. B-9: periode lama hanya dinonaktifkan, tidak dihapus.
     */
    protected static function booted(): void
    {
        static::saved(function (self $periode): void {
            if (! $periode->aktif) {
                return;
            }

            static::query()
                ->whereKeyNot($periode->getKey())
                ->where('aktif', true)
                ->update(['aktif' => false]);
        });

        // Foreign key sudah menolak, tapi galatnya SQLSTATE[23000] yang tidak
        // bisa dibaca pengurus.
        static::deleting(function (self $periode): void {
            $jumlah = $periode->jabatan()->count();

            if ($jumlah > 0) {
                throw HapusIndukException::periodeMasihPunyaJabatan($periode->nama, $jumlah);
            }
        });
    }

    /**
     * @param  Builder<$this>  $query
     */
    public function scopeAktif(Builder $query): void
    {
        $query->where('aktif', true);
    }

    /**
     * @return HasMany<Jabatan, $this>
     */
    public function jabatan(): HasMany
    {
        return $this->hasMany(Jabatan::class, 'periode_id');
    }
}
