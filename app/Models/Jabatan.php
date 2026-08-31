<?php

namespace App\Models;

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
