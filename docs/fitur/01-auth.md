# 01 — Autentikasi & akun

**Status:** selesai · **Butuh:** — · **Berikutnya:** 02-anggota-struktur

Dokumen mandiri. Tempel file ini saja untuk mengerjakan fitur ini.

---

## Tujuan

Anggota bisa mendaftar dan masuk memakai username. Pendaftar baru tidak langsung aktif — harus disetujui pemegang jabatan dulu.

## Kenapa username, bukan email

Sebagian besar anggota perguruan tidak punya atau jarang membuka email. Memaksa email membuat pendaftaran gagal di langkah pertama. Konsekuensinya: reset kata sandi tidak bisa lewat email, jadi ditangani manual (lihat A-7).

---

## Tabel yang disentuh

```
users
  id
  nama              string
  username          string, unique, dipakai login
  email             string, nullable          -- opsional, hanya untuk reset mandiri
  no_hp             string, nullable
  password          string
  is_editor         boolean, default false
  is_guru_besar     boolean, default false
  is_sekben         boolean, default false
  is_admin          boolean, default false
  foto              string, nullable
  harus_ganti_sandi boolean, default false    -- true setelah reset oleh pengurus
  remember_token
  timestamps
  deleted_at
```

Kolom keanggotaan (tingkat, sabuk, ranting) ada di tabel `members` — selebihnya dikerjakan di fitur 02. Tapi status `pending` butuh tempat sejak sekarang (A-4, A-6), jadi fitur 01 sudah membuat `members` versi minimal:

```
members
  id
  user_id           FK users, unique
  status            enum(pending, aktif, non_aktif, alumni), default pending
  tanggal_gabung    date
  timestamps
```

Kolom sisanya — `nia`, `no_warga`, `tingkat_keanggotaan`, `tingkatan`, `tingkatan_urutan`, `ranting_id`, `tanggal_naik_warga`, `iuran_override` — ditambahkan di fitur 02 lewat migration lanjutan, **bukan** dengan membuat ulang tabelnya.

---

## Aturan

- **A-1** Username: 4–20 karakter, hanya `a-z`, `0-9`, dan `_`. Unik. Cek ketersediaan secara langsung di form (AJAX saat selesai mengetik), jangan tunggu submit
  - Endpoint: `GET /cek-username?username=...` → JSON `{ username, valid, tersedia }`. `username` dikembalikan dalam bentuk lowercase, `valid` menilai pola di atas, `tersedia` menilai pola **dan** belum terpakai
  - Username milik akun yang sudah di-soft-delete tetap terhitung **tidak tersedia**, karena barisnya masih memegang unique index. Kalau tidak begitu, form menjanjikan sesuatu yang akan ditolak database saat submit
  - Dibatasi **30 permintaan per menit per IP**. Endpoint ini memang harus menjawab "sudah dipakai atau belum", jadi ia membocorkan username mana yang terdaftar — kebalikan dari tujuan A-5. Batas itu menahannya supaya tidak praktis dipakai memanen daftar username
- **A-2** Form daftar berisi tepat 4 field: nama lengkap, username, kata sandi, konfirmasi kata sandi. Tidak ada field lain
- **A-3** Kata sandi minimal 8 karakter. Tidak ada syarat kombinasi simbol — ini organisasi kecil, prioritaskan kemudahan diingat
- **A-4** Setelah daftar, JANGAN auto-login. Tampilkan halaman konfirmasi: "Pendaftaran kamu sudah masuk. Pengurus akan meninjau dan menghubungimu." Akun berstatus `pending`
- **A-5** Login gagal menampilkan pesan generik: "Username atau kata sandi salah." Jangan bedakan antara username tidak ada dan kata sandi salah — itu membocorkan username mana yang terdaftar
- **A-6** Akun `pending` yang mencoba login mendapat pesan berbeda: "Akun kamu masih menunggu persetujuan pengurus."
- **A-7** Reset kata sandi: Guru Besar / Sekben Umum / Admin menekan tombol "Reset kata sandi" di panel kelola anggota → sistem membuat sandi sementara acak → **ditampilkan sekali di layar** untuk disampaikan lewat WhatsApp → kolom `harus_ganti_sandi` diset true
- **A-8** Akun dengan `harus_ganti_sandi = true` diarahkan ke halaman ganti sandi setiap kali login, dan tidak bisa mengakses halaman lain sampai menggantinya
- **A-9** Rate limit login: 5 percobaan per menit per IP
- **A-10** Kata sandi di-hash bcrypt. Username disimpan lowercase supaya `Riyadi` dan `riyadi` tidak jadi dua akun berbeda
- **A-11** Username **tidak pernah ditampilkan di halaman publik** — murni kredensial. Yang tampil di publik adalah nama dan (kalau ada) NIA/no warga
- **A-12** Akun berstatus `non_aktif` atau `alumni` juga ditolak saat masuk, dengan pesan yang berbeda dari A-6: "Akun kamu sedang tidak aktif. Hubungi pengurus kalau ini keliru." Dibedakan supaya pendaftar yang belum ditinjau tidak tertukar dengan bekas anggota. Sama seperti A-6, status baru diperiksa **setelah** kata sandi terbukti benar — jadi bukan alat menebak username

---

## Halaman

**`/daftar`** — form pendaftaran (A-2), latar `--canvas`, kartu form di tengah maksimal 420px, tombol utama "Daftar". Di bawah form: tautan "Sudah punya akun? Masuk"

**`/masuk`** — form login, 2 field + checkbox "Ingat saya", tombol utama "Masuk". Di bawah: tautan "Belum punya akun? Daftar" dan teks kecil "Lupa kata sandi? Hubungi pengurus."

**`/ganti-sandi`** — muncul paksa kalau `harus_ganti_sandi = true`. Field: sandi baru, konfirmasi

Ikuti `docs/design-tokens.md`. Form pakai input dengan border `--line`, fokus `--ink-muted`.

---

## Kriteria selesai

- [x] Migration `users` dengan seluruh kolom di atas
- [x] Model `User` dengan cast boolean untuk keempat kolom hak akses
- [x] Registrasi berfungsi, akun masuk sebagai pending
- [x] Login berfungsi dengan username
- [x] Pesan error sesuai A-5, A-6, dan A-12
- [x] Cek ketersediaan username langsung di form
- [x] Alur ganti sandi paksa berfungsi
- [x] Rate limit aktif
- [x] Seeder akun uji (lihat bawah)
- [x] Tampilan diverifikasi Playwright di 1440px dan 390px

## Skenario uji

1. Daftar dengan username yang sudah ada → ditolak dengan pesan jelas
2. Daftar dengan username berisi spasi atau huruf besar → ditolak atau dinormalkan
3. Login dengan akun pending → pesan A-6, bukan pesan A-5
4. Login dengan username benar tapi sandi salah → pesan A-5
5. Login dengan username tidak terdaftar → pesan A-5 yang sama persis dengan no.4
6. Akun yang sandinya baru direset → dipaksa ke halaman ganti sandi, tidak bisa ke halaman lain
7. Enam kali gagal login dalam semenit → ditolak sementara
8. Login dengan akun berstatus `non_aktif` → pesan A-12 ("Akun kamu sedang tidak aktif…"), bukan pesan A-6
9. Login dengan akun berstatus `alumni` → pesan A-12 yang sama persis dengan no.8, bukan pesan tersendiri

## Akun uji (seeder)

```
Admin        adminmin     / adminamin123
Guru Besar   gurubesar    / gurusuhu212
Sekben Umum  sekbenuang   / uangUang123
Editor       editorcoba1  / editedit1
Warga        wargacoba1   / wargawarga1
Anggota      anggotacoba1 / anggotaanggota1
```

Semua berstatus aktif kecuali satu akun tambahan `pendingcoba1 / pendingpending1` yang sengaja dibiarkan pending untuk menguji A-6.

Sandi ini hanya untuk pengembangan. Ganti sebelum dipakai sungguhan.
