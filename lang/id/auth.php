<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Pesan Autentikasi
    |--------------------------------------------------------------------------
    |
    | Catatan A-5: alur masuk aplikasi ini TIDAK memakai `failed` di bawah.
    | MasukController menulis sendiri pesan generik "Username atau kata sandi
    | salah." supaya username tidak ada dan sandi salah tidak bisa dibedakan.
    | Kunci di sini tetap diterjemahkan sebagai jaring pengaman kalau suatu saat
    | ada jalur yang memakai pesan bawaan.
    |
    */

    'failed' => 'Username atau kata sandi salah.',
    'password' => 'Kata sandi salah.',
    'throttle' => 'Terlalu banyak percobaan masuk. Coba lagi dalam :seconds detik.',

];
