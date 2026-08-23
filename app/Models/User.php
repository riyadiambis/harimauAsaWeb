<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

// Kolom hak akses (is_editor, is_guru_besar, is_sekben, is_admin) dan
// harus_ganti_sandi sengaja tidak fillable — hanya Admin yang boleh mengubahnya
// (B-6), jadi jangan sampai tertembus mass-assignment dari form pendaftaran.
#[Fillable(['nama', 'username', 'email', 'no_hp', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'is_editor' => 'boolean',
            'is_guru_besar' => 'boolean',
            'is_sekben' => 'boolean',
            'is_admin' => 'boolean',
            'harus_ganti_sandi' => 'boolean',
        ];
    }

    /**
     * Aturan validasi username (A-1): 4–20 karakter, hanya a-z, 0-9, dan _.
     *
     * Dipakai bersama oleh form pendaftaran dan endpoint cek ketersediaan supaya
     * keduanya tidak pernah berbeda pendapat soal username mana yang sah.
     *
     * @return array<int, string>
     */
    public static function aturanUsername(): array
    {
        return ['required', 'string', 'min:4', 'max:20', 'regex:/^[a-z0-9_]+$/'];
    }

    /**
     * Username selalu disimpan lowercase supaya "Riyadi" dan "riyadi" tidak
     * jadi dua akun berbeda (A-10).
     */
    protected function username(): Attribute
    {
        return Attribute::make(
            set: fn (string $value) => Str::lower($value),
        );
    }

    /**
     * Data keanggotaan: tingkat, sabuk, ranting, dan status (pending/aktif).
     *
     * @return HasOne<Member, $this>
     */
    public function member(): HasOne
    {
        return $this->hasOne(Member::class);
    }
}
