# PRD — Portal Anggota & Sistem Kas Pertalian Silat Harimau Asa

**Versi:** 4.0
**Tanggal:** 23 Agustus 2026
**Penyusun:** Rahmat Riyadi
**Status:** Acuan aktif. Versi sebelumnya tidak berlaku.

> **Cara pakai dokumen ini.** PRD adalah referensi keputusan dan skema lengkap — jarang dibaca utuh saat mengerjakan kode. Untuk mengerjakan fitur, pakai `docs/fitur/*.md` yang mandiri per fitur. Kalau file fitur bertentangan dengan PRD, file fitur yang menang karena lebih baru.

### Riwayat perubahan

| Versi | Perubahan besar |
|---|---|
| 1.0 | Draf awal — sistem kas, role Bendahara/Pengurus, login email |
| 2.0 | Role dirombak; login username; jabatan jadi teks bebas; tambah hero slider & galeri; koreksi nama resmi "Harimau Asa" |
| 3.0 | Detail alur autentikasi username; spesifikasi visual beranda & galeri |
| 4.0 | **Rombakan besar.** Role dipisah jadi tingkat keanggotaan + hak akses boolean; hanya Warga kena kas bulanan; jabatan jadi tabel hierarkis dengan periode kepengurusan; tambah tabel wilayah & ranting; `nominal_tagihan` dipisah dari `nominal_bayar`; tambah `no_warga`, `tingkatan_urutan`; halaman `/profil` dan `/struktur` dipisah; fitur **Kumpulan** dan **LMS** masuk cakupan |

---

## 1. Ringkasan

Aplikasi web tunggal yang menggabungkan **profil publik** perguruan Pertalian Silat Harimau Asa dan **portal anggota** berisi sistem iuran kas otomatis.

Sisi publik memperkenalkan perguruan lewat halaman profil, struktur kepengurusan, artikel, pengumuman, dan galeri. Sisi anggota menangani tagihan kas bulanan, denda keterlambatan otomatis, pembayaran lewat QRIS, verifikasi oleh Sekben Umum, pengumpulan dana insidental, dan pustaka materi jurus untuk Warga.

Di-deploy sendiri di mini PC dengan domain sendiri. Tanpa payment gateway berbayar — pembayaran diverifikasi lewat kombinasi kode unik nominal dan bukti transfer.

## 2. Masalah yang dipecahkan

| Masalah sekarang | Solusi |
|---|---|
| Sekben menagih manual satu per satu tiap bulan | Tagihan terbit otomatis tanggal 1 |
| Tidak ada konsekuensi jelas untuk keterlambatan | Denda berjalan otomatis tanpa campur tangan |
| Catatan kas di buku/spreadsheet, rawan hilang dan tidak transparan | Riwayat permanen, anggota bisa lihat sendiri |
| Bukti transfer bertebaran di grup WhatsApp | Terkumpul di satu antrean verifikasi |
| Struktur kepengurusan tidak terdokumentasi | Bagan hierarkis per periode, terarsip |
| Materi jurus tersebar, sulit diakses Warga | Pustaka video terkategori |
| Perguruan belum punya kehadiran online | Profil, artikel, pengumuman, galeri publik |

## 3. Tujuan

1. Sekben Umum tidak perlu menerbitkan tagihan atau menghitung denda secara manual
2. Anggota bisa melihat status tagihan dan riwayatnya kapan saja
3. Setiap perubahan status pembayaran, keanggotaan, dan jabatan punya jejak audit permanen
4. Perguruan punya halaman publik yang layak dibagikan, termasuk struktur kepengurusan
5. Biaya operasional bulanan mendekati nol

## 4. Bukan tujuan (v1)

- Aplikasi mobile native
- Payment gateway berbayar
- Absensi latihan
- Sistem ujian kenaikan tingkat (LMS hanya pustaka referensi, tanpa progres atau kuis)
- Multi-perguruan
- Pembayaran cicilan atau sebagian
- Chat realtime (lihat bagian 15)

---

## 5. Pengguna, tingkat, dan hak akses

### 5.1 Tiga sumbu yang terpisah

Ini konsep paling penting di dokumen ini. Versi lama mencampur ketiganya jadi satu kolom `role` dan itu menghasilkan pertentangan. Sekarang dipisah:

**Sumbu 1 — Tingkat keanggotaan** (`members.tingkat_keanggotaan`)
Nilai: `anggota` atau `warga`. Satu orang punya tepat satu.
Menentukan **kewajiban kas**: hanya `warga` yang kena tagihan bulanan.

**Sumbu 2 — Hak akses** (kolom boolean di `users`)
`is_editor`, `is_guru_besar`, `is_sekben`, `is_admin`. Bisa lebih dari satu, bisa dicabut.
Menentukan **apa yang boleh dilakukan** di aplikasi.

**Sumbu 3 — Jabatan struktural** (tabel `jabatan`)
Label posisi di bagan kepengurusan, terikat periode, teks bebas.
Murni **penampilan di bagan** — tidak memengaruhi kas maupun hak akses.

Konsekuensinya, dan ini disengaja: seorang Warga yang diangkat jadi Guru Besar **tetap Warga dan tetap bayar kas**. Jabatan di bagan boleh apa saja tanpa mengubah kewajiban atau izin sistemnya.

### 5.2 Hak akses

| Hak | Yang bisa dilakukan |
|---|---|
| *(tanpa hak khusus)* | Dashboard pribadi, tagihan sendiri, pengumuman internal, profil sendiri |
| `is_editor` | + kelola artikel, pengumuman, galeri, gambar hero beranda |
| `is_guru_besar` | + ubah tingkat keanggotaan, sabuk, no warga, jabatan, status anggota; setujui pendaftar; kelola LMS |
| `is_sekben` | Sama seperti Guru Besar, **plus** seluruh urusan kas: periode, verifikasi, pemutihan denda, kumpulan, laporan, rekonsiliasi |
| `is_admin` | + pengaturan sistem, kelola pengguna, dan **satu-satunya** yang bisa memberi/mencabut keempat hak ini |

Admin **tidak** otomatis bisa mengubah sabuk atau jabatan — itu wewenang keilmuan, bukan wewenang teknis. Kalau Admin perlu melakukannya, dia harus juga diberi `is_guru_besar` atau `is_sekben`.

### 5.3 Tingkatan sabuk (resmi AD/ART)

| enum | urutan | tampilan |
|---|---|---|
| `hitam_polos` | 1 | Hitam / Polos |
| `kuning` | 2 | Kuning |
| `oren` | 3 | Oren |
| `merah_warga_1` | 4 | Merah — Warga Tingkat 1 |
| `merah_warga_2` | 5 | Merah — Warga Tingkat 2 |
| `putih_warga_3` | 6 | Putih — Warga Tingkat 3 |

`tingkatan_urutan` diisi otomatis lewat mutator. Kolom ini ada supaya pengurutan "tertinggi ke terendah" eksplisit, tidak bergantung perilaku enum MySQL.

### 5.4 Nomor identitas

- **`nia`** — nomor induk anggota. Dimiliki semua orang sejak akun disetujui.
- **`no_warga`** — nomor pada kartu tanda warga perguruan. Hanya diberikan saat naik ke tingkat Warga. Null sebelum itu.

---

## 6. Arah desain

Rujukan lengkap: `docs/design-tokens.md`. Ringkasnya:

Gaya **soft minimalism** — netral hangat, kontras rendah, ruang lega. Palet greige dengan aksen oxblood `#7A3B33` yang dipakai hemat. Plus Jakarta Sans untuk teks, JetBrains Mono untuk angka. Radius kartu 16px, jarak antar section 32px, bayangan nyaris tak terlihat.

Tiga elemen sengaja tidak simetris dan tidak boleh "dirapikan" jadi grid biasa: **kolase foto miring** di halaman About, **linimasa sejarah berbentuk lingkaran berpanah**, dan **pita 12 bulan** di dashboard.

Adaptasi mobile: desktop 1440px / mobile 390px, responsif mulai 360px. Sidebar jadi drawer, grid jadi satu kolom, tabel jadi kartu vertikal. **Bagan struktur di mobile jadi daftar bertingkat yang bisa di-expand**, bukan pohon bergaris — pohon 3 tingkat tidak muat di 390px.

---

## 7. Peta situs

```
PUBLIK
  /                     Beranda (hero slider, pengumuman, artikel, galeri)
  /profil               About: sejarah, visi-misi, kolase foto, riwayat Guru Besar
  /struktur             Bagan kepengurusan + daftar Warga + daftar Anggota
  /artikel              Daftar artikel
  /artikel/{slug}       Detail artikel
  /pengumuman           Pengumuman publik
  /pengumuman/{slug}    Detail pengumuman
  /galeri               Galeri foto kegiatan
  /daftar               Formulir pendaftaran
  /masuk                Login

ANGGOTA (butuh login)
  /dashboard            Isi berbeda menurut tingkat keanggotaan
  /kas                  Tagihan & riwayat (Warga)
  /kas/{id}             Detail tagihan + rincian denda
  /kas/{id}/bayar       Pembayaran (QRIS + kode unik + unggah bukti)
  /kumpulan             Daftar pengumpulan dana yang menyangkut dirinya
  /materi               LMS pustaka jurus (khusus Warga)
  /materi/{slug}        Detail materi
  /pengumuman-internal  Pengumuman khusus anggota
  /profil-saya          Data diri, ganti kata sandi
  /ganti-sandi          Paksa ganti setelah reset

PENGELOLA
  /admin/anggota        Kelola anggota, setujui pendaftar, reset sandi
  /admin/struktur       Periode kepengurusan & jabatan
  /admin/wilayah        Wilayah & ranting
  /admin/riwayat-gubes  Riwayat Guru Besar (manual)
  /admin/periode        Periode kas
  /admin/verifikasi     Antrean bukti pembayaran
  /admin/kumpulan       Kelola pengumpulan dana
  /admin/konten         Artikel & pengumuman
  /admin/beranda        Gambar hero slider
  /admin/galeri         Galeri foto
  /admin/materi         LMS: kategori & materi
  /admin/laporan        Rekap kas & rekonsiliasi
  /admin/log            Audit log
```

---

## 8. Skema database

```
users
  id, nama, username (unique, login), email (nullable), no_hp (nullable), password,
  is_editor, is_guru_besar, is_sekben, is_admin  (boolean, default false),
  foto (nullable), harus_ganti_sandi (boolean, default false),
  remember_token, timestamps, deleted_at

members
  user_id (FK unique), nia (unique), no_warga (nullable, unique),
  tingkat_keanggotaan enum(anggota|warga) default anggota,
  tingkatan enum(hitam_polos|kuning|oren|merah_warga_1|merah_warga_2|putih_warga_3),
  tingkatan_urutan tinyint,
  ranting_id (FK nullable), tanggal_gabung, tanggal_naik_warga (nullable),
  status enum(pending|aktif|non_aktif|alumni) default pending,
  iuran_override (nullable), timestamps

wilayah
  id, nama, urutan, timestamps

ranting
  id, wilayah_id (FK), nama, urutan, timestamps

periode_kepengurusan
  id, nama, tahun_mulai, tahun_selesai, aktif (bool), timestamps

jabatan
  id, periode_id (FK), user_id (FK), nama_jabatan (teks bebas),
  parent_id (FK jabatan, nullable, self-ref), ranting_id (FK nullable),
  urutan, timestamps

riwayat_guru_besar
  id, nama, tahun_mulai, tahun_selesai, foto (nullable),
  keterangan (nullable), urutan, timestamps

periods                                    -- periode kas bulanan
  id, bulan, tahun, nominal_dasar, tanggal_buka, tanggal_tutup,
  status enum(draft|aktif|ditutup), timestamps
  unique (bulan, tahun)

kumpulan
  id, nama, deskripsi, nominal, target enum(semua|anggota|warga|pilih),
  tanggal_buka, tanggal_tutup, status enum(draft|aktif|ditutup),
  dibuat_oleh (FK users), timestamps, deleted_at

invoices
  id, user_id (FK),
  tipe enum(kas|kumpulan),
  period_id (FK nullable)      -- terisi bila tipe = kas
  kumpulan_id (FK nullable)    -- terisi bila tipe = kumpulan
  nominal_tagihan              -- jumlah item, STABIL, dipakai laporan
  nominal_bayar (nullable)     -- nominal_tagihan + kode unik, hanya saat kode aktif
  status enum(pending|menunggu_verifikasi|lunas|dibatalkan),
  kode_unik (nullable), kode_expired_at (nullable), paid_at (nullable),
  timestamps, deleted_at
  unique (user_id, period_id), unique (user_id, kumpulan_id)
  index (status, kode_expired_at)

invoice_items
  id, invoice_id (FK), tipe enum(iuran|denda), deskripsi, nominal,
  urutan_denda (nullable), dihapuskan (bool), alasan_penghapusan (nullable),
  timestamps
  unique (invoice_id, tipe, urutan_denda)

payments
  id, invoice_id (FK), metode enum(upload_manual|tunai|notifikasi_otomatis),
  nominal_terdeteksi, bukti_path, bukti_hash, ref_id,
  raw_payload (json), hasil_ai (json),
  status enum(menunggu|disetujui|ditolak),
  verified_by (FK users), verified_at, catatan,
  timestamps, deleted_at
  index (bukti_hash), index (status)

posts
  id, tipe enum(artikel|pengumuman), judul, slug (unique), ringkasan,
  konten (longtext), cover_path,
  status enum(draft|terbit), visibilitas enum(publik|anggota),
  penting (bool), expired_at (nullable),
  published_at, author_id (FK), timestamps, deleted_at
  index (tipe, status, visibilitas)

post_images
  id, post_id (FK), path, path_thumb, alt, urutan, timestamps

hero_images
  id, path, path_thumb, judul (nullable), urutan, aktif (bool),
  uploaded_by (FK users), timestamps

gallery_items
  id, path, path_thumb, keterangan (nullable), urutan,
  uploaded_by (FK users), timestamps, deleted_at

kategori_materi
  id, nama, slug (unique), urutan, timestamps

materi
  id, kategori_id (FK), judul, slug (unique), deskripsi,
  youtube_id, urutan, terbit (bool),
  dibuat_oleh (FK users), timestamps, deleted_at

audit_logs
  id, actor_id (FK), aksi, subject_type, subject_id,
  before (json), after (json), ip, created_at
  index (subject_type, subject_id)

settings
  setting_key (PK), value (json), updated_at
```

**Catatan `settings`.** Kolomnya `setting_key`, bukan `key` — `KEY` adalah reserved word MySQL. Isinya hal yang bisa berubah tanpa deploy ulang: nominal iuran default, besaran denda, batas denda, panjang jendela bayar, jam eksekusi job, path gambar QRIS, nomor kantong bank. Jangan hardcode.

**Catatan `invoices`.** Kolom `tipe` memungkinkan Kumpulan memakai ulang seluruh alur pembayaran dan verifikasi kas tanpa menduplikasi kode.

---

## 9. Aturan bisnis

Bagian paling penting. Kalau ada pertentangan dengan bagian lain, bagian ini yang menang.

### Kas & tagihan

- **BR-1** Nominal tagihan **tidak pernah diubah langsung**. Semua perubahan lewat penambahan baris di `invoice_items`. `nominal_tagihan` selalu hasil hitung ulang dari jumlah item yang tidak `dihapuskan`.
- **BR-2** `nominal_tagihan` (stabil) terpisah dari `nominal_bayar` (nominal + kode unik, nullable). Laporan dan rekap **selalu** memakai `nominal_tagihan`. Pencocokan pembayaran memakai `nominal_bayar`.
- **BR-3** Denda maksimal 3 kali per tagihan. Dijaga unique index `(invoice_id, tipe, urutan_denda)` di level database, bukan hanya di kode aplikasi.
- **BR-4** Semua job terjadwal wajib idempoten. Dijalankan berulang menghasilkan keadaan akhir yang sama.
- **BR-5** Kode unik membekukan nominal. Job denda **wajib melewati** tagihan yang `kode_expired_at`-nya di masa depan, dan memprosesnya pada eksekusi berikutnya.
- **BR-6** Keunikan diperiksa pada **`nominal_bayar`**, bukan `kode_unik` saja. Rp5.043 dan Rp6.043 boleh aktif bersamaan karena nominalnya berbeda.
- **BR-7** Jika 99 percobaan pengambilan kode gagal, tampilkan "Sedang banyak yang membayar, coba lagi beberapa menit lagi". Jangan pernah memberi kode yang bertabrakan.
- **BR-8** Data pembayaran tidak pernah dihapus permanen. Soft delete. Penolakan mengubah status, tidak menghapus baris.
- **BR-9** Sistem tidak pernah meluluskan pembayaran otomatis hanya berdasarkan tangkapan layar. Tangkapan layar adalah alat bantu isi form, bukan bukti sah.

### Keanggotaan

- **BR-10** Yang kena tagihan kas bulanan: `tingkat_keanggotaan = warga` **DAN** `status = aktif`. Tingkat `anggota` tidak kena kas bulanan — mereka hanya kena Kumpulan.
- **BR-11** Anggota yang naik ke tingkat Warga mulai ditagih **bulan berikutnya**, tidak pernah bulan berjalan — walau naiknya tanggal 1.
- **BR-12** `nama_jabatan` teks bebas. Sistem TIDAK memvalidasinya terhadap daftar apa pun. Yang di-enum hanya `tingkatan` sabuk.
- **BR-13** Hanya boleh ada satu `periode_kepengurusan` dengan `aktif = true`. Menandai periode baru aktif otomatis menonaktifkan yang lama. Periode lama tidak dihapus.
- **BR-14** `riwayat_guru_besar` diisi manual dan sengaja terpisah dari tabel `jabatan`, karena Guru Besar dari masa sebelum sistem ini ada tidak punya akun.
- **BR-15** Wewenang mengubah tingkat, sabuk, no warga, jabatan, dan status anggota ada pada `is_guru_besar` dan `is_sekben` saja. Memberi/mencabut hak akses ada pada `is_admin` saja.

### Umum

- **BR-16** Zona waktu aplikasi `Asia/Makassar`. Semua tanggal batas dihitung terhadap zona ini, bukan UTC.
- **BR-17** Audit log wajib untuk: perubahan status pembayaran, pemutihan denda, perubahan status/tingkat/sabuk/no warga anggota, perubahan jabatan, dan pemberian/pencabutan hak akses. Isi: pelaku, waktu, nilai sebelum, nilai sesudah.
- **BR-18** Dana kas masuk ke satu kantong Bank Jago khusus yang **tidak dipakai untuk apa pun selain kas**. Karena itu saldo kantong harus sama persis dengan total tercatat sistem; selisih berapa pun ditandai anomali.
- **BR-19** Username tidak pernah ditampilkan di halaman publik — murni kredensial.
- **BR-20** Materi LMS hanya bisa diakses `tingkat_keanggotaan = warga`. Video di YouTube berstatus **unlisted**, bukan public.

---

## 10. Alur kas: simulasi

Iuran Rp5.000, jendela bayar tanggal 1–5.

| Tanggal | Yang terjadi | `nominal_tagihan` |
|---|---|---|
| 1 Agu 00:05 | Job terbitkan tagihan, item "Iuran Agustus" | 5.000 |
| 1–5 Agu | Bebas denda | 5.000 |
| 6 Agu 00:05 | Job denda tambah item denda ke-1 | 6.000 |
| 6 Sep 00:05 | Denda ke-2 | 7.000 |
| 6 Okt 00:05 | Denda ke-3 | 8.000 |
| 6 Nov dst | Mentok, tidak bertambah | 8.000 |

Saat anggota menekan "Bayar" pada tagihan Rp6.000: sistem memberi kode 43, `nominal_bayar` jadi Rp6.043 dan berlaku 3 jam. Selama itu job denda melewati tagihan ini (BR-5). Kalau kode kedaluwarsa tanpa pembayaran, `nominal_bayar` dikosongkan dan denda menyusul di eksekusi berikutnya.

---

## 11. Job terjadwal

```
* * * * * cd /path/project && php artisan schedule:run >> /dev/null 2>&1
```

| Perintah | Jadwal (WITA) | Tugas |
|---|---|---|
| `kas:terbitkan-tagihan` | Tgl 1, 00:05 | Tagihan untuk semua Warga aktif |
| `kas:terapkan-denda` | Tgl 6, 00:05 | Tambah item denda sesuai BR-3 dan BR-5 |
| `kas:lepas-kode` | Tiap 10 menit | Bebaskan kode unik kedaluwarsa |
| `kas:pengingat` | Tgl 3, 5, 6 pukul 08:00 | Notifikasi tagihan; tgl 5 berisi peringatan denda besok |
| `kas:backup` | Harian 02:00 | Dump database + salin folder unggahan |

Semua perintah harus bisa dijalankan manual dengan aman untuk uji coba.

---

## 12. Kebutuhan non-fungsional

**Rekening penerima.** Satu kantong Bank Jago khusus, tidak dipakai transaksi lain. Dijadikan Kantong Bersama dengan Guru Besar atau pengurus lain supaya saldo terlihat lebih dari satu orang. Nomor kantong dan gambar QR disimpan di `settings`. Halaman bayar menampilkan nomor kantong dengan tombol salin sebagai alternatif memindai QR.

**Keamanan.** Bcrypt. Rate limit login 5/menit/IP. Unggahan divalidasi MIME asli bukan ekstensi. Folder unggahan tidak boleh mengeksekusi PHP. Kredensial hanya di `.env`.

**Performa.** Halaman publik di bawah 2 detik pada 3G. Gambar dikompres dan dilayani sebagai WebP dua ukuran (asli maks 1600px, thumbnail 400px). Daftar panjang memakai paginasi.

**Keandalan.** Backup harian disimpan 30 hari. Backup **wajib diuji restore minimal sekali** sebelum sistem dipakai untuk uang sungguhan.

**Aksesibilitas.** Kontras teks minimal 4.5:1 — perlu perhatian ekstra karena palet ini sengaja berkontras rendah. Fokus keyboard terlihat. Semua gambar punya teks alternatif.

---

## 13. Tumpukan teknologi

| Lapisan | Pilihan |
|---|---|
| Framework | Laravel 12 |
| Tampilan | Blade + Tailwind CSS + Alpine.js |
| Basis data | MySQL 8 / MariaDB |
| Panel admin | Filament (tidak wajib ikut design tokens) |
| Pemroses gambar | Intervention Image |
| Antrean | Laravel Queue driver database |
| Server | Nginx atau Caddy dalam Docker |
| Akses publik | Cloudflare Tunnel (tanpa IP publik, HTTPS otomatis) |
| Domain | `.my.id` |

**Tooling agen:** Playwright MCP (verifikasi tampilan) dan Laravel Boost (skema & dokumentasi). Detail di `docs/Setup-Claude-Code.md`.

---

## 14. Urutan pengerjaan

Rujukan aktif: `docs/ROADMAP.md`. Ringkasnya 13 fitur berurutan — autentikasi, keanggotaan & struktur, periode kas, denda, pembayaran, verifikasi, dashboard, kumpulan, halaman publik, konten, beranda & galeri, laporan, LMS.

**Sistem sudah bisa dipakai sungguhan setelah fitur 07 (dashboard).** Sisanya pengembangan, bukan prasyarat.

---

## 15. Belum diputuskan

**Chat grup internal.** Ide obrolan mirip WhatsApp untuk anggota yang login. Belum masuk cakupan karena chat realtime butuh koneksi persisten (WebSocket) yang jauh lebih berat daripada halaman biasa — mini PC rumahan akan terasa bebannya saat banyak orang membuka bersamaan, apalagi lewat Cloudflare Tunnel. Alternatif ringan yang bisa dipertimbangkan: papan diskusi non-realtime. Diputuskan setelah fitur 07 selesai dan beban server sesungguhnya terlihat.

**Otomatisasi pembayaran (fase 2).** Webhook penerima notifikasi bank dengan verifikasi HMAC, pencocokan otomatis nominal ke tagihan, pemantau detak jantung listener. Ditunda sampai alur manual terbukti jalan.

**Lapisan AI (fase 2).** Pembacaan bukti transfer untuk mengisi form verifikasi, tanya-jawab bahasa alami atas data kas, rekap naratif bulanan.

---

## 16. Skenario uji wajib

Sebelum sistem dipakai untuk uang sungguhan:

1. Job penerbitan dijalankan dua kali → tidak ada tagihan ganda
2. Job denda dijalankan dua kali di hari yang sama → tidak ada denda ganda
3. Tagihan telat 4 bulan → total denda tetap Rp3.000
4. Anggota ambil kode pukul 23:50 tanggal 5, job denda jalan 00:05 → `nominal_bayar` tidak berubah selama kode aktif
5. Kode kedaluwarsa tanpa pembayaran → denda menyusul di eksekusi berikutnya
6. Dua orang ambil kode bersamaan → `nominal_bayar` keduanya berbeda
7. Anggota berstatus alumni → tidak muncul di penerbitan tagihan
8. Anggota tingkat `anggota` → tidak muncul di penerbitan tagihan kas bulanan
9. Anggota naik jadi Warga tanggal 1 → ditagih mulai bulan berikutnya
10. Bukti yang sama diunggah dua kali → sistem menandai duplikat
11. Pemutihan denda → `nominal_tagihan` berkurang, item tetap ada dengan flag dan alasan
12. Backup di-restore ke database kosong → seluruh data utuh
13. Total tercatat sistem vs saldo kantong → selisih nol; selisih yang dibuat sengaja ditandai halaman rekonsiliasi
14. Admin tanpa `is_guru_besar`/`is_sekben` mencoba ubah sabuk → ditolak
15. Guru Besar mencoba mengubah `is_admin` orang lain → ditolak
16. Isi `nama_jabatan` dengan teks apa pun → diterima tanpa validasi enum
17. Tandai periode kepengurusan baru aktif → yang lama otomatis nonaktif, datanya tetap ada
18. Anggota (bukan Warga) membuka `/materi` → ditolak

---

## 17. Glosarium

| Istilah | Arti |
|---|---|
| Tingkat keanggotaan | `anggota` atau `warga` — penentu kewajiban kas bulanan |
| Warga | Tingkat keanggotaan penuh; kena kas bulanan, punya no warga, dapat akses LMS |
| Anggota | Tingkat di bawah Warga; tidak kena kas bulanan, hanya Kumpulan |
| NIA | Nomor induk anggota, dimiliki semua orang sejak akun disetujui |
| No warga | Nomor pada kartu tanda warga, diberikan saat naik ke tingkat Warga |
| Tingkatan sabuk | Enam tingkat resmi AD/ART, dari Hitam/Polos sampai Putih Warga 3 |
| Jabatan | Label posisi di bagan kepengurusan; teks bebas, terikat periode, punya atasan |
| Periode kepengurusan | Masa bakti kepengurusan, mis. "Kepengurusan 2026-2027" |
| Periode kas | Satu bulan tagihan iuran |
| Tagihan (invoice) | Kewajiban satu orang untuk satu periode kas atau satu kumpulan |
| Item tagihan | Baris komponen di dalam tagihan (iuran atau denda) |
| Kode unik | 2 digit yang ditambahkan ke nominal agar transfer bisa dicocokkan |
| Jendela bayar | Tanggal 1–5, rentang tanpa denda |
| Kumpulan | Pengumpulan dana insidental di luar kas bulanan |
| Pemutihan | Pembatalan denda oleh Sekben Umum dengan alasan tercatat |
| Rekonsiliasi | Pencocokan total tercatat sistem dengan saldo kantong bank |
| Guru Besar | Hak akses tertinggi urusan keilmuan & keanggotaan; diberikan Admin, bisa dicabut |
| Sekben Umum | Hak akses setara Guru Besar plus seluruh urusan kas |
| Editor | Hak akses konten (artikel, pengumuman, galeri, beranda) |
| LMS | Pustaka video materi jurus khusus Warga, tanpa progres atau kuis |
| Pita 12 bulan | Deretan 12 kotak status kas di dashboard Warga |
