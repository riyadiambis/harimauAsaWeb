<?php

namespace App\Models;

use App\Models\Concerns\MencatatAudit;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table('ranting')]
#[Fillable(['wilayah_id', 'nama', 'urutan'])]
class Ranting extends Model
{
    use HasFactory, MencatatAudit;

    /**
     * B-10 lewat B-16.
     *
     * @return array<int, string>
     */
    public function kolomDiaudit(): array
    {
        return ['wilayah_id', 'nama', 'urutan'];
    }

    /**
     * Penghapusan wajib tercatat: ranting yang hilang membuat
     * `members.ranting_id` anggotanya jadi null tanpa meninggalkan jejak lain.
     *
     * @return array<int, string>
     */
    public function peristiwaDiaudit(): array
    {
        return ['created', 'updated', 'deleted'];
    }

    /**
     * @return BelongsTo<Wilayah, $this>
     */
    public function wilayah(): BelongsTo
    {
        return $this->belongsTo(Wilayah::class);
    }

    /**
     * @return HasMany<Member, $this>
     */
    public function members(): HasMany
    {
        return $this->hasMany(Member::class);
    }
}
