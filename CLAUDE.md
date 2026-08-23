# Portal Anggota & Kas — Pertalian Silat Harimau Asa

Aplikasi web tunggal: profil publik perguruan + portal anggota berisi sistem iuran kas otomatis.
Self-hosted di mini PC, tanpa payment gateway berbayar.

## Stack

Laravel 13.23, Blade + Tailwind + Alpine.js, MySQL, Filament (khusus panel admin).
Timezone aplikasi: `Asia/Makassar`. Bahasa antarmuka: Indonesia.

## Perintah

- `php artisan serve` — server dev
- `npm run dev` — **WAJIB jalan saat mengubah tampilan** (Tailwind watch). Kalau tidak, class baru tidak menghasilkan CSS dan halaman terlihat berantakan tanpa error.
- `php artisan test` — jalankan tes
- `php artisan migrate:fresh --seed` — reset database + data uji

## Dokumen

- `docs/ROADMAP.md` — urutan pengerjaan & status tiap fitur. **Baca ini dulu tiap sesi baru.**
- `docs/fitur/*.md` — spesifikasi per fitur. Sumber kebenaran. Satu file per sesi kerja.
- `docs/design-tokens.md` — warna, tipografi, pola komponen. Wajib dibaca sebelum menyentuh view.
- `docs/PRD.md` — arsip keputusan. Jarang dibaca utuh.

Kalau isi file fitur bertentangan dengan PRD, **file fitur yang menang** (lebih baru).

## Aturan tampilan

Detail lengkap di `docs/design-tokens.md`. Yang paling sering dilanggar:

- DILARANG memakai warna di luar variabel CSS terdaftar
- DILARANG ikon dekoratif besar sebagai pengisi ruang kosong. Ikon hanya bila ukurannya disebut eksplisit dalam spesifikasi
- Bayangan maksimal `0 1px 3px rgba(38,37,31,0.06)`. Tanpa gradien, tanpa glow
- Jarak antar section 32px, padding kartu 24px, radius kartu 16px
- Jarak antar elemen di dalam kartu pakai `gap` di parent flex, bukan margin per anak
- Judul pakai sentence case, bukan Title Case

## Aturan bisnis yang paling sering dilanggar

- Nominal tagihan **tidak pernah diubah langsung**. Semua perubahan lewat penambahan baris di `invoice_items`
- Denda maksimal 3 kali per tagihan, dijaga unique index `(invoice_id, tipe, urutan_denda)` di level database
- Semua job terjadwal wajib **idempoten** — dijalankan dua kali menghasilkan keadaan akhir yang sama
- `nominal_tagihan` (stabil, untuk laporan) berbeda dari `nominal_bayar` (nominal + kode unik, hanya terisi saat kode aktif)
- Yang kena tagihan kas bulanan hanya `tingkat_keanggotaan = warga` DAN `status = aktif`
- `jabatan.nama_jabatan` teks bebas — TIDAK ada enum atau daftar tetap
- Data pembayaran tidak pernah dihapus permanen. Pakai soft delete
- Setiap perubahan status pembayaran, pemutihan denda, perubahan status/tingkat anggota, dan perubahan jabatan wajib menulis audit log

## Cara kerja yang saya harapkan

- **Satu fitur atau satu section per permintaan.** Jangan borong satu halaman sekaligus
- Setelah mengubah tampilan, **verifikasi pakai Playwright** — screenshot desktop 1440px dan mobile 390px, cek tumpang tindih dan jarak, perbaiki, ulangi sampai bersih, baru lapor
- Kalau ragu soal skema database, query lewat Laravel Boost. Jangan menebak dari file migration
- Kalau spesifikasi tidak menyebut sesuatu, tanya. Jangan mengarang default

## Compact

Saat merangkum percakapan, pertahankan: daftar file yang diubah, perintah tes yang dipakai, dan keputusan desain yang sudah disepakati.
