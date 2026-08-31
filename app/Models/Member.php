<?php

namespace App\Models;

use App\Models\Concerns\MencatatAudit;
use App\Support\PenomoranNia;
use Database\Factories\MemberFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\Rule;

// tingkatan_urutan sengaja TIDAK fillable — nilainya diturunkan dari `tingkatan`
// lewat mutator di bawah, tidak pernah diketik manual.
#[Fillable([
    'user_id',
    'nia',
    'no_warga',
    'tingkat_keanggotaan',
    'tingkatan',
    'ranting_id',
    'tanggal_gabung',
    'tanggal_naik_warga',
    'status',
    'iuran_override',
])]
class Member extends Model
{
    /** @use HasFactory<MemberFactory> */
    use HasFactory, MencatatAudit;

    /**
     * Sejalan dengan default kolom di database, supaya instance baru tidak
     * mengembalikan null sebelum di-reload. tingkatan_urutan dipasang menyertai
     * tingkatan karena default di sini tidak melewati mutator.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => 'pending',
        'tingkat_keanggotaan' => 'anggota',
        'tingkatan' => 'hitam_polos',
        'tingkatan_urutan' => 1,
    ];

    /**
     * Aturan validasi `no_warga` (B-13): tepat 8 digit angka dan unik.
     *
     * Berbeda dari `nia` yang digenerate sistem, nomor ini disalin manual dari
     * kartu tanda warga fisik yang sudah ada — jadi validasilah yang menjaga
     * bentuknya, bukan generator.
     *
     * `digits:8` menolak karakter non-angka apa pun, termasuk tanda plus dan
     * titik desimal, jadi "0012 3456" maupun "+1234567" tidak lolos.
     *
     * @return array<int, mixed>
     */
    public static function aturanNoWarga(?int $abaikanId = null): array
    {
        return [
            'nullable',
            'digits:8',
            Rule::unique('members', 'no_warga')->ignore($abaikanId),
        ];
    }

    /**
     * B-2 (tingkat, sabuk, no_warga) dan B-5 (status).
     *
     * @return array<int, string>
     */
    public function kolomDiaudit(): array
    {
        return ['tingkat_keanggotaan', 'tingkatan', 'no_warga', 'status'];
    }

    /**
     * Peta tingkatan sabuk → urutan. Sumbernya tabel di
     * docs/fitur/02-anggota-struktur.md.
     */
    public const URUTAN_TINGKATAN = [
        'hitam_polos' => 1,
        'kuning' => 2,
        'oren' => 3,
        'merah_warga_1' => 4,
        'merah_warga_2' => 5,
        'putih_warga_3' => 6,
    ];

    /**
     * Nama tampilan tiap tingkatan.
     */
    public const LABEL_TINGKATAN = [
        'hitam_polos' => 'Hitam / Polos',
        'kuning' => 'Kuning',
        'oren' => 'Oren',
        'merah_warga_1' => 'Merah — Warga Tingkat 1',
        'merah_warga_2' => 'Merah — Warga Tingkat 2',
        'putih_warga_3' => 'Putih — Warga Tingkat 3',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tanggal_gabung' => 'date',
            'tanggal_naik_warga' => 'date',
            'tingkatan_urutan' => 'integer',
            'iuran_override' => 'integer',
        ];
    }

    /**
     * B-1: nomor induk diberikan begitu pendaftaran disetujui — yaitu saat
     * status beranjak dari `pending`. Dipasang di event `saving` supaya berlaku
     * lewat jalur mana pun, dan supaya nomornya ikut tertulis di query yang
     * sama, bukan lewat UPDATE kedua.
     *
     * Unique index pada `nia` yang menjadi penjamin akhir: kalau dua persetujuan
     * terjadi bersamaan dan menghitung nomor yang sama, yang kedua gagal dan
     * perlu diulang — bukan menghasilkan nomor kembar.
     */
    protected static function booted(): void
    {
        static::saving(function (self $member): void {
            if ($member->nia !== null || $member->status === 'pending') {
                return;
            }

            $member->nia = PenomoranNia::berikutnya(
                ($member->tanggal_gabung ?? now())->year
            );
        });
    }

    /**
     * Menetapkan `tingkatan` sekaligus mengisi `tingkatan_urutan`, supaya
     * pengurutan "tertinggi ke terendah" tidak bergantung pada urutan enum MySQL.
     */
    protected function tingkatan(): Attribute
    {
        return Attribute::make(
            set: fn (string $value) => [
                'tingkatan' => $value,
                'tingkatan_urutan' => self::URUTAN_TINGKATAN[$value] ?? 1,
            ],
        );
    }

    public function labelTingkatan(): string
    {
        return self::LABEL_TINGKATAN[$this->tingkatan] ?? $this->tingkatan;
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Ranting, $this>
     */
    public function ranting(): BelongsTo
    {
        return $this->belongsTo(Ranting::class);
    }
}
