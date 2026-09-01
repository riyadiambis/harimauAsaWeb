<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * B-19: rantai atasan jabatan tidak boleh membentuk lingkaran.
 */
class SiklusAtasanException extends RuntimeException
{
    public static function dirinyaSendiri(string $nama): self
    {
        return new self("\"{$nama}\" tidak bisa jadi atasan dirinya sendiri.");
    }

    public static function sudahDiRantaiAtasan(string $nama, string $calon): self
    {
        return new self(
            "\"{$calon}\" tidak bisa jadi atasan \"{$nama}\", karena \"{$nama}\" "
            .'sudah ada di rantai atasannya. Bagan akan berputar tanpa ujung.'
        );
    }
}
