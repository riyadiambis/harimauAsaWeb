<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * B-14: pagar supaya sistem tidak bisa kehilangan seluruh Admin-nya.
 */
class HakAdminException extends RuntimeException
{
    public static function cabutSendiri(): self
    {
        return new self('Hak admin tidak bisa dicabut sendiri. Minta admin lain yang melakukannya.');
    }

    public static function adminTerakhir(): self
    {
        return new self('Hak admin terakhir tidak bisa dicabut. Sistem harus selalu punya minimal satu admin.');
    }

    public static function hapusAdminTerakhir(): self
    {
        return new self('Akun admin terakhir tidak bisa dihapus. Angkat admin lain lebih dulu.');
    }
}
