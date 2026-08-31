<?php

namespace App\Models;

use App\Exceptions\HakAdminException;
use App\Models\Concerns\MencatatAudit;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasName;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
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
class User extends Authenticatable implements FilamentUser, HasName
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, MencatatAudit, Notifiable, SoftDeletes;

    /**
     * Menyamakan nilai awal instance baru dengan default kolom di database.
     * Tanpa ini, model yang baru dibuat mengembalikan null untuk kolom yang
     * tidak ikut di INSERT — dan pemeriksaan hak akses jadi null, bukan false.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'is_editor' => false,
        'is_guru_besar' => false,
        'is_sekben' => false,
        'is_admin' => false,
        'harus_ganti_sandi' => false,
    ];

    /**
     * B-6: pemberian dan pencabutan hak akses.
     *
     * @return array<int, string>
     */
    public function kolomDiaudit(): array
    {
        return ['is_editor', 'is_guru_besar', 'is_sekben', 'is_admin'];
    }

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
     * B-14: sistem harus selalu punya minimal satu Admin.
     *
     * Dijaga di level model, bukan hanya di policy, karena ini invarian — sekali
     * dilanggar tidak ada lagi yang bisa mengatur hak akses, dan pemulihannya
     * harus lewat database langsung. Penghapusan akun ikut dijaga karena
     * menghapus Admin terakhir merusak invarian yang sama dengan mencabut
     * benderanya.
     */
    protected static function booted(): void
    {
        static::saving(function (self $user): void {
            // Hanya pencabutan yang dijaga — pemberian hak admin selalu boleh.
            if (! $user->exists || ! $user->isDirty('is_admin') || $user->is_admin) {
                return;
            }

            if (auth()->id() === $user->getKey()) {
                throw HakAdminException::cabutSendiri();
            }

            if ($user->adminTerakhir()) {
                throw HakAdminException::adminTerakhir();
            }
        });

        static::deleting(function (self $user): void {
            // Sengaja membaca nilai tersimpan, bukan nilai di memori: instance
            // bisa saja sudah dikotori percobaan pencabutan yang gagal, dan
            // is_admin = false di memori akan membuat pagar ini terlewat.
            if ((bool) $user->getOriginal('is_admin') && $user->adminTerakhir()) {
                throw HakAdminException::hapusAdminTerakhir();
            }
        });
    }

    /**
     * Filament menampilkan nama pengguna lewat kontrak ini. Tanpa itu ia mencari
     * kolom `name`, sedangkan kolom di aplikasi ini bernama `nama` — dan panel
     * gagal dengan TypeError saat menyusun avatar bawaannya.
     */
    public function getFilamentName(): string
    {
        return $this->nama;
    }

    /**
     * B-15: gerbang masuk panel pengelola.
     *
     * Yang boleh membuka /admin adalah pengguna dengan minimal satu dari empat
     * kolom hak akses bernilai true. Anggota biasa — yang tidak memegang satu
     * pun — ditolak di pintu.
     *
     * Ini gerbang masuk saja. Apa yang boleh dilakukan di dalamnya tetap
     * ditentukan policy B-2, B-4, B-5, dan B-6; seorang Editor yang lolos ke
     * sini tetap tidak bisa mengubah sabuk atau hak akses siapa pun.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return $this->is_editor
            || $this->is_guru_besar
            || $this->is_sekben
            || $this->is_admin;
    }

    /**
     * Apakah dia satu-satunya Admin yang tersisa. Akun yang sudah di-soft-delete
     * tidak dihitung — scope bawaan SoftDeletes yang menyaringnya.
     */
    public function adminTerakhir(): bool
    {
        return static::query()
            ->where('is_admin', true)
            ->whereKeyNot($this->getKey())
            ->doesntExist();
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

    /**
     * Jabatan struktural yang dipegang, bisa lebih dari satu dan terikat periode.
     *
     * @return HasMany<Jabatan, $this>
     */
    public function jabatan(): HasMany
    {
        return $this->hasMany(Jabatan::class);
    }
}
