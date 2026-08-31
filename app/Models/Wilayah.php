<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table('wilayah')]
#[Fillable(['nama', 'urutan'])]
class Wilayah extends Model
{
    use HasFactory;

    /**
     * @return HasMany<Ranting, $this>
     */
    public function ranting(): HasMany
    {
        return $this->hasMany(Ranting::class);
    }
}
