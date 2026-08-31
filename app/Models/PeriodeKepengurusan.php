<?php

namespace App\Models;

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
    use HasFactory;

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
