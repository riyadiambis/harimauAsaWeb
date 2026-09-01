# Setup Claude Code — Portal Harimau Asa

Catatan tooling untuk project ini: apa yang sudah terpasang, apa yang belum, dan cara kerja yang menghindari masalah kemarin.

---

## Status pemasangan

| Komponen | Status | Catatan |
|---|---|---|
| Claude Code | ✅ terpasang | v2.1.239, installer native Windows, di `%USERPROFILE%\.local\bin` |
| Playwright MCP | ✅ terpasang | Terdaftar di scope lokal project ini |
| Browser Playwright | ✅ terunduh | Chromium, Firefox, WebKit |
| Laravel | ✅ terpasang | Laravel 13.23 di `webHarimauAsa/` |
| Laravel Boost | ⬜ belum | Pasang setelah migration pertama jadi (lihat bawah) |
| CLAUDE.md | ✅ ada | Di root project |
| Skill review desain | ⬜ belum | Buat sebelum fitur 07 (dashboard) |

---

## Menyiapkan mesin baru: berkas env

Dua berkas env **tidak ikut git** karena memuat kredensial: `.env` dan `.env.testing`. Keduanya punya contoh yang ikut git — salin, lalu isi bagian rahasianya.

```powershell
cp .env.example .env
cp .env.testing.example .env.testing
php artisan key:generate
php artisan key:generate --env=testing
```

Lalu isi `DB_PASSWORD` di keduanya sesuai MySQL di mesin itu.

**Kalau `.env.testing` lupa disalin**, `php artisan test` akan jatuh ke `.env` dan menjalankan tes terhadap **database pengembangan** `harimau_asa` — bukan `harimau_asa_test`. Tes yang memakai `RefreshDatabase` akan mengosongkan data kerjamu tanpa peringatan apa pun. Salin dulu sebelum menjalankan tes pertama kali.

Dua hal yang sengaja **tidak** ada di `.env.testing.example`, dan jangan ditambahkan:

- **`APP_TIMEZONE`** — dibiarkan kosong supaya `ZonaWaktuTest` benar-benar menguji nilai bawaan `config/app.php` (Asia/Makassar, BR-16). Kalau dipin di env uji, tes tetap hijau walau konfigurasinya diam-diam kembali ke UTC.
- **`DB_*` di `phpunit.xml`** — nilai `<env>` phpunit menang atas dotenv, jadi menyetelnya di sana akan memblokir `.env.testing` dan mengarahkan tes kembali ke database pengembangan.

---

## 1. Playwright MCP — mata untuk Claude Code

Sudah terpasang. Inilah yang memperbaiki masalah terbesar sesi sebelumnya: dulu Claude menulis kode tampilan tanpa pernah tahu hasilnya di browser, jadi kamu satu-satunya yang bisa melihat. Sekarang dia bisa memeriksa sendiri.

**Syarat sebelum dipakai:** server dev harus jalan di terminal terpisah.

```powershell
php artisan serve      # terminal 1
npm run dev            # terminal 2 — WAJIB, lihat catatan di bawah
```

**Cara memakainya.** Tutup setiap permintaan tampilan dengan instruksi verifikasi:

```
Setelah selesai, pakai Playwright: buka http://127.0.0.1:8000/<halaman>,
screenshot di viewport 1440px dan 390px, periksa apakah ada elemen
tumpang tindih, ikon yang tidak diminta, atau jarak yang meleset dari
design-tokens. Kalau ada, perbaiki dan screenshot ulang sampai bersih.
Baru lapor ke saya.
```

Kalimat "sampai bersih, baru lapor" itu yang penting. Tanpa itu, Playwright terpasang pun tidak otomatis dipakai.

**Catatan biaya.** Tiap panggilan browser makan beberapa detik dan token. Pakai untuk meninjau halaman, jangan untuk mengubah satu margin.

**Kalau perlu memasang ulang** (misal pindah folder project):

```powershell
claude mcp add playwright npx @playwright/mcp@latest
```

Konfigurasi MCP tersimpan per-project, jadi folder baru butuh pendaftaran ulang.

---

## 2. Laravel Boost — pengetahuan Laravel & skema database

**Belum dipasang. Pasang setelah fitur 01 selesai**, yaitu setelah ada migration dan tabel sungguhan — sebelum itu tidak ada yang bisa diinspeksi.

```powershell
composer require laravel/boost --dev
php artisan boost:install
```

Installer-nya interaktif: pilih fitur yang mau dipasang (guidelines, skills, MCP server) dan pilih Claude Code sebagai agent.

Kalau tidak terdeteksi otomatis:

```powershell
claude mcp add -s local -t stdio laravel-boost php artisan boost:mcp
```

**Yang didapat:** inspeksi skema database langsung, daftar route, konfigurasi, baca error terakhir dari log, jalankan Tinker untuk memverifikasi asumsi, dan pencarian dokumentasi Laravel sesuai versi terpasang.

**Kenapa penting untuk project ini:** skema kita besar — 20+ tabel dengan banyak enum dan relasi. Tanpa Boost, tiap kali Claude butuh tahu apakah kolomnya `tingkatan` atau `tingkatan_urutan`, dia membaca file migration satu per satu dan sering salah tebak.

**Tes setelah pasang:** tanya "kolom apa saja di tabel members?" Kalau dia menjawab dari database, bukan dari membaca file, berarti berhasil.

---

## 3. Skill review desain — belum dibuat

Rencananya `.claude/skills/review-desain/SKILL.md`, berisi checklist yang dipakai Claude setelah mengubah tampilan: ada ikon yang tidak diminta? jarak section 32px? warna di luar daftar? kontras cukup? tumpang tindih di 390px?

Bedanya dengan `design-tokens.md`: tokens berisi **aturan**, skill berisi **prosedur pemeriksaan**. Skill dimuat hanya saat relevan, jadi boleh detail tanpa membebani context.

Belum mendesak — fitur 01 dan 02 hampir semuanya backend. Buat sebelum fitur 07 (dashboard), saat tampilan mulai jadi bagian besar pekerjaan.

---

## 4. Anggaran context

Setiap MCP server dan skill yang aktif memakan context window, terpakai atau tidak. Kalau context habis untuk daftar tool, yang tersisa untuk kode jadi sedikit dan kualitas justru turun.

Patokan: di bawah 10 MCP server aktif, di bawah 30 tool total. Untuk project ini **dua sudah cukup** — Playwright dan Boost.

Jangan tergoda memasang banyak MCP setelah membaca artikel tooling. Context7 (dokumentasi paket pihak ketiga) boleh ditambah nanti kalau kita memakai paket di luar ekosistem Laravel dan Claude sering salah menebak API-nya. Belum perlu sekarang.

---

## 5. Alur kerja per sesi

1. Buka `docs/ROADMAP.md` — lihat fitur mana yang sedang dikerjakan
2. Tempel **satu** file dari `docs/fitur/` — jangan lebih
3. Kerjakan satu bagian, verifikasi, baru lanjut bagian berikutnya
4. Setelah fitur selesai, perbarui statusnya di ROADMAP

`CLAUDE.md` dan `design-tokens.md` dibaca otomatis oleh Claude Code — tidak perlu ditempel.

**Mulai sesi baru ketika:** ganti fitur, atau percakapan sudah bercabang ke 3-4 hal berbeda. Context yang berantakan menghasilkan kode yang berantakan.

---

## 6. Tiga kebiasaan yang tetap harus dijaga

Tooling tidak mengganti disiplin. Ini yang menyebabkan kekacauan sesi lalu, dan tidak ada MCP yang bisa memperbaikinya:

**Satu bagian per permintaan.** Kalau kamu minta perbaiki 6 hal sekaligus dan hasilnya meleset, kamu tidak tahu bagian mana yang gagal. Ini penyebab nomor satu, bukan modelnya.

**Minta verifikasi, bukan cuma perubahan.** Lihat pola prompt di bagian 1.

**Pastikan `npm run dev` jalan.** Kalau Tailwind tidak sedang di-watch, class baru yang ditulis Claude tidak menghasilkan CSS sama sekali. Halaman terlihat "hampir benar tapi berantakan" — persis seperti beberapa screenshot sesi lalu, dan tidak ada pesan error apa pun yang memberi tahu kamu.

---

## 7. Kalau Claude Code tiba-tiba mati

Gejala: `claude` gagal jalan dengan pesan "not a valid application for this OS platform".

Penyebab: bug pada Windows di mana `claude.exe` tergantikan file placeholder ~500 byte, biasanya setelah `claude update`.

Cek dulu ukurannya:

```powershell
dir "$env:USERPROFILE\.local\bin\claude.exe"
```

Kalau ukurannya kecil (bukan ratusan MB), pasang ulang:

```powershell
irm https://claude.ai/install.ps1 | iex
```

Lalu tutup terminal sepenuhnya dan buka lagi.


## Komentar di kode

- Jangan menulis komentar yang mengulang apa yang sudah jelas
  dari kodenya. Kalau bisa diwakili nama fungsi atau variabel
  yang baik, pakai itu, jangan komentar
- Komentar yang menjelaskan KENAPA tetap ditulis, terutama untuk
  keputusan yang terlihat aneh tanpa penjelasannya, atau yang
  menutup lubang yang pernah ada. Ujinya: kalau komentarnya
  dihapus dan kodenya jadi tampak seperti kesalahan, komentar
  itu harus ada
- Panjangnya secukupnya. Satu sampai dua baris, bukan paragraf
- Aturan nomor (A-x, B-x, BR-x) cukup dirujuk nomornya, jangan
  disalin isinya. Isinya ada di docs/fitur/