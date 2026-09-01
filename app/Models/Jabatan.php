<?php

namespace App\Models;

use App\Exceptions\SiklusAtasanException;
use App\Models\Concerns\MencatatAudit;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

// B-3: nama_jabatan teks bebas — tidak ada enum, tidak ada daftar tetap, dan
// tidak divalidasi terhadap apa pun.
#[Table('jabatan')]
#[Fillable(['periode_id', 'user_id', 'nama_jabatan', 'parent_id', 'ranting_id', 'urutan'])]
class Jabatan extends Model
{
    use HasFactory, MencatatAudit;

    /**
     * B-4 mencakup "mengisi/mengubah", jadi pembuatan dan penghapusan baris
     * ikut dicatat — bukan hanya perubahan kolom.
     *
     * @return array<int, string>
     */
    public function kolomDiaudit(): array
    {
        return ['periode_id', 'user_id', 'nama_jabatan', 'parent_id', 'ranting_id', 'urutan'];
    }

    /**
     * @return array<int, string>
     */
    public function peristiwaDiaudit(): array
    {
        return ['created', 'updated', 'deleted'];
    }

    /**
     * B-19: rantai atasan tidak boleh melingkar.
     *
     * Ditelusuri sampai akar, bukan sekadar membandingkan dengan diri sendiri —
     * A→B→A juga melingkar. Dipasang di model supaya panel, tinker, dan seeder
     * sama-sama terjaga; bagan /struktur menelusuri parent secara rekursif dan
     * akan berputar tanpa ujung kalau satu lingkaran lolos.
     */
    protected static function booted(): void
    {
        static::saving(function (self $jabatan): void {
            if ($jabatan->parent_id === null || ! $jabatan->isDirty('parent_id')) {
                return;
            }

            if ($jabatan->parent_id === $jabatan->getKey()) {
                throw SiklusAtasanException::dirinyaSendiri($jabatan->nama_jabatan);
            }

            $calon = static::find($jabatan->parent_id);

            if ($calon === null || $jabatan->getKey() === null) {
                return;
            }

            foreach ($calon->rantaiAtasan() as $atasan) {
                if ($atasan->getKey() === $jabatan->getKey()) {
                    throw SiklusAtasanException::sudahDiRantaiAtasan(
                        $jabatan->nama_jabatan,
                        $calon->nama_jabatan,
                    );
                }
            }
        });
    }

    /**
     * Dirinya sendiri lalu seluruh atasannya sampai akar.
     *
     * Punya penjaga langkah supaya data yang sudah terlanjur melingkar — dari
     * sebelum B-19 ada, atau dari perubahan langsung di database — tidak membuat
     * ini menggantung.
     *
     * @return list<self>
     */
    public function rantaiAtasan(): array
    {
        $rantai = [];
        $simpul = $this;
        $langkah = 0;

        while ($simpul !== null && $langkah++ < 64) {
            $rantai[] = $simpul;
            $simpul = $simpul->parent_id === null ? null : static::find($simpul->parent_id);
        }

        return $rantai;
    }

    /**
     * Jabatan yang boleh dipilih sebagai atasan: satu periode, bukan dirinya
     * sendiri, dan bukan bawahannya (B-19).
     *
     * @return list<int>
     */
    public function idCalonAtasan(): array
    {
        $terlarang = [$this->getKey(), ...$this->idSeluruhBawahan()];

        return static::query()
            ->where('periode_id', $this->periode_id)
            ->whereNotIn('id', array_filter($terlarang))
            ->pluck('id')
            ->all();
    }

    /**
     * @return list<int>
     */
    private function idSeluruhBawahan(): array
    {
        $id = [];
        $antre = [$this->getKey()];
        $langkah = 0;

        while ($antre !== [] && $langkah++ < 64) {
            $anak = static::whereIn('parent_id', $antre)->pluck('id')->all();
            $anak = array_values(array_diff($anak, $id));

            if ($anak === []) {
                break;
            }

            $id = [...$id, ...$anak];
            $antre = $anak;
        }

        return $id;
    }

    /**
     * @return BelongsTo<PeriodeKepengurusan, $this>
     */
    public function periode(): BelongsTo
    {
        return $this->belongsTo(PeriodeKepengurusan::class, 'periode_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Atasan di bagan.
     *
     * @return BelongsTo<Jabatan, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * Bawahan langsung di bagan.
     *
     * @return HasMany<Jabatan, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    /**
     * @return BelongsTo<Ranting, $this>
     */
    public function ranting(): BelongsTo
    {
        return $this->belongsTo(Ranting::class);
    }
}
