<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Penolakan hapus induk yang masih punya anak, sesuai tabel "Aturan hapus induk"
 * di docs/fitur/02-anggota-struktur.md.
 *
 * Foreign key `restrictOnDelete` sudah menjaga di level database, tapi galatnya
 * berupa SQLSTATE[23000] yang tidak bisa dibaca pengurus. Pagar di model ini
 * yang mengubahnya jadi kalimat, dan database tetap jadi jaring terakhir kalau
 * suatu saat ada jalur yang melewati Eloquent.
 */
class HapusIndukException extends RuntimeException
{
    public static function wilayahMasihPunyaRanting(string $nama, int $jumlah): self
    {
        return new self(
            "Wilayah \"{$nama}\" masih punya {$jumlah} ranting. "
            .'Pindahkan atau hapus rantingnya lebih dulu.'
        );
    }
}
