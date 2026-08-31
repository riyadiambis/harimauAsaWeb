<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Pesan Validasi
    |--------------------------------------------------------------------------
    |
    | Terjemahan pesan galat bawaan validator. Seluruh kunci Laravel 13 ada di
    | sini, jadi fallback ke bahasa Inggris (config/app.php) praktis tidak
    | pernah terpakai untuk aturan bawaan.
    |
    | Pesan yang lebih spesifik boleh ditulis di `messages()` milik masing-masing
    | FormRequest — itu menang atas berkas ini. Yang di sini adalah jaring
    | pengaman supaya field mana pun yang belum diberi pesan khusus tetap keluar
    | dalam bahasa Indonesia.
    |
    | `:attribute` diganti nama manusiawi dari daftar `attributes` di bawah.
    |
    */

    'accepted' => ':attribute harus disetujui.',
    'accepted_if' => ':attribute harus disetujui bila :other adalah :value.',
    'active_url' => ':attribute bukan URL yang valid.',
    'after' => ':attribute harus tanggal setelah :date.',
    'after_or_equal' => ':attribute harus tanggal setelah atau sama dengan :date.',
    'alpha' => ':attribute hanya boleh berisi huruf.',
    'alpha_dash' => ':attribute hanya boleh berisi huruf, angka, tanda hubung, dan garis bawah.',
    'alpha_num' => ':attribute hanya boleh berisi huruf dan angka.',
    'any_of' => ':attribute tidak valid.',
    'array' => ':attribute harus berupa larik.',
    'ascii' => ':attribute hanya boleh berisi huruf, angka, dan simbol satu byte.',
    'base64' => ':attribute harus berupa teks Base64 yang valid.',
    'before' => ':attribute harus tanggal sebelum :date.',
    'before_or_equal' => ':attribute harus tanggal sebelum atau sama dengan :date.',
    'between' => [
        'array' => ':attribute harus berisi antara :min sampai :max item.',
        'file' => ':attribute harus berukuran antara :min sampai :max kilobyte.',
        'numeric' => ':attribute harus bernilai antara :min sampai :max.',
        'string' => ':attribute harus terdiri dari :min sampai :max karakter.',
    ],
    'boolean' => ':attribute harus bernilai benar atau salah.',
    'can' => ':attribute berisi nilai yang tidak diizinkan.',
    'confirmed' => 'Konfirmasi :attribute tidak cocok.',
    'contains' => ':attribute belum memuat nilai yang diperlukan.',
    'current_password' => 'Kata sandi salah.',
    'date' => ':attribute bukan tanggal yang valid.',
    'date_equals' => ':attribute harus tanggal yang sama dengan :date.',
    'date_format' => ':attribute harus sesuai format :format.',
    'decimal' => ':attribute harus punya :decimal angka di belakang koma.',
    'declined' => ':attribute harus ditolak.',
    'declined_if' => ':attribute harus ditolak bila :other adalah :value.',
    'different' => ':attribute dan :other harus berbeda.',
    'digits' => ':attribute harus terdiri dari :digits digit angka.',
    'digits_between' => ':attribute harus terdiri dari :min sampai :max digit angka.',
    'dimensions' => 'Dimensi gambar :attribute tidak sesuai.',
    'distinct' => ':attribute berisi nilai yang kembar.',
    'doesnt_contain' => ':attribute tidak boleh memuat salah satu dari: :values.',
    'doesnt_end_with' => ':attribute tidak boleh diakhiri salah satu dari: :values.',
    'doesnt_start_with' => ':attribute tidak boleh diawali salah satu dari: :values.',
    'email' => ':attribute bukan alamat email yang valid.',
    'encoding' => ':attribute harus memakai pengodean :encoding.',
    'ends_with' => ':attribute harus diakhiri salah satu dari: :values.',
    'enum' => ':attribute yang dipilih tidak valid.',
    'exists' => ':attribute yang dipilih tidak ditemukan.',
    'extensions' => ':attribute harus berekstensi salah satu dari: :values.',
    'file' => ':attribute harus berupa berkas.',
    'filled' => ':attribute wajib diisi.',
    'gt' => [
        'array' => ':attribute harus berisi lebih dari :value item.',
        'file' => ':attribute harus lebih besar dari :value kilobyte.',
        'numeric' => ':attribute harus lebih besar dari :value.',
        'string' => ':attribute harus lebih dari :value karakter.',
    ],
    'gte' => [
        'array' => ':attribute harus berisi :value item atau lebih.',
        'file' => ':attribute harus :value kilobyte atau lebih.',
        'numeric' => ':attribute harus bernilai :value atau lebih.',
        'string' => ':attribute harus terdiri dari :value karakter atau lebih.',
    ],
    'hex_color' => ':attribute bukan kode warna heksadesimal yang valid.',
    'image' => ':attribute harus berupa gambar.',
    'in' => ':attribute yang dipilih tidak valid.',
    'in_array' => ':attribute tidak ada di dalam :other.',
    'in_array_keys' => ':attribute harus memuat setidaknya satu kunci berikut: :values.',
    'integer' => ':attribute harus berupa bilangan bulat.',
    'ip' => ':attribute bukan alamat IP yang valid.',
    'ipv4' => ':attribute bukan alamat IPv4 yang valid.',
    'ipv6' => ':attribute bukan alamat IPv6 yang valid.',
    'json' => ':attribute harus berupa teks JSON yang valid.',
    'list' => ':attribute harus berupa daftar.',
    'lowercase' => ':attribute harus ditulis dengan huruf kecil.',
    'lt' => [
        'array' => ':attribute harus berisi kurang dari :value item.',
        'file' => ':attribute harus lebih kecil dari :value kilobyte.',
        'numeric' => ':attribute harus lebih kecil dari :value.',
        'string' => ':attribute harus kurang dari :value karakter.',
    ],
    'lte' => [
        'array' => ':attribute tidak boleh berisi lebih dari :value item.',
        'file' => ':attribute tidak boleh lebih besar dari :value kilobyte.',
        'numeric' => ':attribute tidak boleh lebih besar dari :value.',
        'string' => ':attribute tidak boleh lebih dari :value karakter.',
    ],
    'mac_address' => ':attribute bukan alamat MAC yang valid.',
    'max' => [
        'array' => ':attribute tidak boleh berisi lebih dari :max item.',
        'file' => ':attribute tidak boleh lebih besar dari :max kilobyte.',
        'numeric' => ':attribute tidak boleh lebih besar dari :max.',
        'string' => ':attribute maksimal :max karakter.',
    ],
    'max_digits' => ':attribute tidak boleh lebih dari :max digit angka.',
    'mimes' => ':attribute harus berupa berkas bertipe: :values.',
    'mimetypes' => ':attribute harus berupa berkas bertipe: :values.',
    'min' => [
        'array' => ':attribute harus berisi setidaknya :min item.',
        'file' => ':attribute harus berukuran setidaknya :min kilobyte.',
        'numeric' => ':attribute harus bernilai setidaknya :min.',
        'string' => ':attribute minimal :min karakter.',
    ],
    'min_digits' => ':attribute harus terdiri dari setidaknya :min digit angka.',
    'missing' => ':attribute tidak boleh ada.',
    'missing_if' => ':attribute tidak boleh ada bila :other adalah :value.',
    'missing_unless' => ':attribute tidak boleh ada kecuali :other adalah :value.',
    'missing_with' => ':attribute tidak boleh ada bila :values terisi.',
    'missing_with_all' => ':attribute tidak boleh ada bila :values semuanya terisi.',
    'multiple_of' => ':attribute harus kelipatan :value.',
    'not_in' => ':attribute yang dipilih tidak valid.',
    'not_regex' => 'Format :attribute tidak sesuai.',
    'numeric' => ':attribute harus berupa angka.',
    'password' => [
        'letters' => ':attribute harus memuat setidaknya satu huruf.',
        'mixed' => ':attribute harus memuat setidaknya satu huruf besar dan satu huruf kecil.',
        'numbers' => ':attribute harus memuat setidaknya satu angka.',
        'symbols' => ':attribute harus memuat setidaknya satu simbol.',
        'uncompromised' => ':attribute ini pernah bocor di kebocoran data. Pilih yang lain.',
    ],
    'present' => ':attribute harus ada.',
    'present_if' => ':attribute harus ada bila :other adalah :value.',
    'present_unless' => ':attribute harus ada kecuali :other adalah :value.',
    'present_with' => ':attribute harus ada bila :values terisi.',
    'present_with_all' => ':attribute harus ada bila :values semuanya terisi.',
    'prohibited' => ':attribute tidak diperbolehkan.',
    'prohibited_if' => ':attribute tidak diperbolehkan bila :other adalah :value.',
    'prohibited_if_accepted' => ':attribute tidak diperbolehkan bila :other disetujui.',
    'prohibited_if_declined' => ':attribute tidak diperbolehkan bila :other ditolak.',
    'prohibited_unless' => ':attribute tidak diperbolehkan kecuali :other ada di dalam :values.',
    'prohibits' => ':attribute membuat :other tidak boleh diisi.',
    'regex' => 'Format :attribute tidak sesuai.',
    'required' => ':attribute wajib diisi.',
    'required_array_keys' => ':attribute harus memuat entri untuk: :values.',
    'required_if' => ':attribute wajib diisi bila :other adalah :value.',
    'required_if_accepted' => ':attribute wajib diisi bila :other disetujui.',
    'required_if_declined' => ':attribute wajib diisi bila :other ditolak.',
    'required_unless' => ':attribute wajib diisi kecuali :other ada di dalam :values.',
    'required_with' => ':attribute wajib diisi bila :values terisi.',
    'required_with_all' => ':attribute wajib diisi bila :values semuanya terisi.',
    'required_without' => ':attribute wajib diisi bila :values tidak terisi.',
    'required_without_all' => ':attribute wajib diisi bila :values sama sekali tidak terisi.',
    'same' => ':attribute harus sama dengan :other.',
    'size' => [
        'array' => ':attribute harus berisi :size item.',
        'file' => ':attribute harus berukuran :size kilobyte.',
        'numeric' => ':attribute harus bernilai :size.',
        'string' => ':attribute harus terdiri dari :size karakter.',
    ],
    'starts_with' => ':attribute harus diawali salah satu dari: :values.',
    'string' => ':attribute harus berupa teks.',
    'timezone' => ':attribute bukan zona waktu yang valid.',
    'unique' => ':attribute ini sudah dipakai.',
    'uploaded' => ':attribute gagal diunggah.',
    'uppercase' => ':attribute harus ditulis dengan huruf besar.',
    'url' => ':attribute bukan URL yang valid.',
    'ulid' => ':attribute bukan ULID yang valid.',
    'uuid' => ':attribute bukan UUID yang valid.',

    /*
    |--------------------------------------------------------------------------
    | Pesan Validasi Khusus per Field
    |--------------------------------------------------------------------------
    |
    | Kalau satu field butuh kalimat sendiri untuk satu aturan, tulis di sini
    | dengan pola "nama_field.nama_aturan".
    |
    */

    'custom' => [
        'no_warga' => [
            'digits' => 'Nomor warga harus tepat 8 digit angka, sesuai yang tertera di kartu tanda warga.',
            'unique' => 'Nomor warga ini sudah terdaftar atas nama anggota lain.',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Nama Field yang Manusiawi
    |--------------------------------------------------------------------------
    |
    | Tanpa daftar ini, :attribute diisi nama kolom dengan garis bawah diganti
    | spasi — "no warga", "tingkat keanggotaan" — yang terbaca seperti salah
    | ketik di form berbahasa Indonesia.
    |
    | Ditulis dengan huruf awal kapital karena hampir semua pesan di atas
    | dimulai dengan :attribute.
    |
    */

    'attributes' => [
        // Akun & autentikasi (fitur 01)
        'nama' => 'Nama lengkap',
        'username' => 'Username',
        'password' => 'Kata sandi',
        'password_confirmation' => 'Konfirmasi kata sandi',
        'email' => 'Email',
        'no_hp' => 'Nomor HP',
        'ingat_saya' => 'Ingat saya',

        // Keanggotaan (fitur 02)
        'nia' => 'NIA',
        'no_warga' => 'Nomor warga',
        'tingkat_keanggotaan' => 'Tingkat keanggotaan',
        'tingkatan' => 'Tingkatan sabuk',
        'tingkatan_urutan' => 'Urutan tingkatan',
        'status' => 'Status keanggotaan',
        'ranting_id' => 'Ranting',
        'wilayah_id' => 'Wilayah',
        'tanggal_gabung' => 'Tanggal gabung',
        'tanggal_naik_warga' => 'Tanggal naik warga',
        'iuran_override' => 'Iuran khusus',

        // Struktur kepengurusan (fitur 02)
        'nama_jabatan' => 'Nama jabatan',
        'periode_id' => 'Periode kepengurusan',
        'parent_id' => 'Jabatan atasan',
        'urutan' => 'Urutan',
    ],

];
