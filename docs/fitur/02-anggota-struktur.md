# 02 — Anggota, tingkatan & struktur

**Status:** dikerjakan · **Butuh:** 01-auth · **Berikutnya:** 03-kas-periode

Dokumen mandiri. Tempel file ini saja untuk mengerjakan fitur ini.

---

## Tujuan

Menyimpan data keanggotaan, tingkatan sabuk, wilayah/ranting, dan struktur kepengurusan yang berhierarki serta terikat masa bakti.

## Konsep kunci: dua sumbu terpisah

Ini yang paling sering salah dipahami, jadi tegas di sini:

**Sumbu 1 — tingkat keanggotaan** (`members.tingkat_keanggotaan`): `anggota` atau `warga`.
Menentukan kewajiban kas. Satu orang punya tepat satu nilai.

**Sumbu 2 — hak akses** (kolom boolean di `users`): `is_editor`, `is_guru_besar`, `is_sekben`, `is_admin`.
Menentukan apa yang boleh dilakukan di aplikasi. Satu orang bisa punya lebih dari satu, bisa dicabut.

**Sumbu 3 — jabatan struktural** (tabel `jabatan`): label posisi di bagan kepengurusan, terikat periode.

Ketiganya independen. Seorang Warga yang diangkat jadi Guru Besar **tetap Warga** — tetap kena tagihan kas. Jabatannya di bagan boleh apa saja, tidak memengaruhi dua sumbu lain.

---

## Tabel

`members` sudah dibuat versi minimalnya di fitur 01 (`id`, `user_id`, `status`, `tanggal_gabung`) karena status `pending` butuh tempat sejak pendaftaran. Fitur ini **menambahkan sisa kolomnya lewat migration baru**, bukan membuat ulang tabelnya.

```
members
  id
  user_id                FK users, unique
  nia                    string, nullable, unique -- nomor induk anggota;
                                                  -- null selama masih pending,
                                                  -- digenerate sistem (B-12)
  no_warga               varchar(8), nullable, unique -- nomor kartu tanda warga,
                                                  -- hanya untuk tingkat warga,
                                                  -- diisi manual (B-13)
  tingkat_keanggotaan    enum(anggota, warga), default anggota
  tingkatan              enum(hitam_polos, kuning, oren,
                              merah_warga_1, merah_warga_2, putih_warga_3),
                         default hitam_polos
  tingkatan_urutan       tinyint, default 1  -- 1..6, lihat tabel di bawah
  ranting_id             FK ranting, nullable
  tanggal_gabung         date
  tanggal_naik_warga     date, nullable
  status                 enum(pending, aktif, non_aktif, alumni), default pending
  iuran_override         integer unsigned, nullable
  timestamps
  index (tingkat_keanggotaan, status)  -- dipakai penerbitan tagihan fitur 03
  index (tingkatan_urutan)

wilayah
  id, nama, urutan, timestamps

ranting
  id, wilayah_id FK, nama, urutan, timestamps

periode_kepengurusan
  id, nama, tahun_mulai, tahun_selesai, aktif boolean, timestamps
  -- contoh: nama "Kepengurusan 2026-2027", aktif true

jabatan
  id
  periode_id      FK periode_kepengurusan
  user_id         FK users
  nama_jabatan    string          -- TEKS BEBAS, tanpa enum
  parent_id       FK jabatan, nullable, self-referencing  -- atasan di bagan
  ranting_id      FK ranting, nullable  -- kalau jabatan terikat ranting tertentu
  urutan          integer
  timestamps

riwayat_guru_besar
  id, nama, tahun_mulai, tahun_selesai nullable, foto nullable,
  keterangan nullable, urutan, timestamps
  -- tahun_selesai null = masih menjabat
  -- diisi manual, TIDAK diambil otomatis dari tabel jabatan

audit_logs
  id
  actor_id        FK users, nullable  -- null = perubahan dari sistem
                                      -- (seeder, job, perintah artisan)
  aksi            string              -- dibuat | diubah | dihapus
  subject_type    string
  subject_id      unsigned bigint
  before          json, nullable
  after           json, nullable
  ip              string, nullable
  created_at                          -- baris audit tidak pernah diubah
  index (subject_type, subject_id), index (created_at)
```

### Aturan hapus induk

Ditegakkan lewat foreign key, bukan hanya di kode:

| Yang dihapus | Akibat pada anaknya |
|---|---|
| `wilayah` yang masih punya ranting | ditolak (restrict) |
| `periode_kepengurusan` yang masih punya jabatan | ditolak (restrict) — sejalan dengan B-9 |
| `ranting` | `members.ranting_id` dan `jabatan.ranting_id` jadi null; barisnya tetap ada |
| `jabatan` induk | bawahannya naik jadi akar (`parent_id` null), tidak ikut terhapus |
| `users` (permanen) | baris `members` dan `jabatan`-nya ikut terhapus (cascade) |

### Peta tingkatan sabuk

| enum | urutan | tampilan |
|---|---|---|
| `hitam_polos` | 1 | Hitam / Polos |
| `kuning` | 2 | Kuning |
| `oren` | 3 | Oren |
| `merah_warga_1` | 4 | Merah — Warga Tingkat 1 |
| `merah_warga_2` | 5 | Merah — Warga Tingkat 2 |
| `putih_warga_3` | 6 | Putih — Warga Tingkat 3 |

`tingkatan_urutan` diisi otomatis lewat mutator di model, jangan diketik manual. Kolom ini ada supaya pengurutan "tertinggi ke terendah" eksplisit dan tidak bergantung pada perilaku enum MySQL.

---

## Aturan

- **B-1** `nia` dimiliki semua orang sejak akun disetujui. `no_warga` hanya diisi saat seseorang naik ke tingkat `warga` — sebelum itu null
- **B-2** Yang berhak mengubah `tingkat_keanggotaan`, `tingkatan`, dan `no_warga`: **Guru Besar dan Sekben Umum saja**. Admin tidak bisa, kecuali dia juga memegang salah satu jabatan itu
- **B-3** `nama_jabatan` teks bebas. Sistem TIDAK memvalidasinya terhadap daftar apa pun
- **B-4** Yang berhak mengisi/mengubah `jabatan`: Guru Besar dan Sekben Umum
- **B-5** Yang berhak mengubah `status` anggota dan menyetujui pendaftar: Guru Besar dan Sekben Umum
- **B-6** Yang berhak mengubah kolom hak akses (`is_editor`, `is_guru_besar`, `is_sekben`, `is_admin`): **Admin saja**
- **B-7** Naik ke tingkat `warga` mengisi `tanggal_naik_warga`. Penagihan kas dimulai **bulan berikutnya**, tidak pernah bulan berjalan — walau naiknya tanggal 1
- **B-8** Hanya boleh ada satu `periode_kepengurusan` dengan `aktif = true`. Menandai periode baru aktif otomatis menonaktifkan yang lama
- **B-9** Periode lama tidak dihapus — jadi arsip yang bisa dilihat
- **B-10** Semua perubahan pada B-2, B-4, B-5, B-6, dan B-16 wajib menulis audit log (pelaku, waktu, nilai sebelum, nilai sesudah). Untuk wilayah, ranting, periode kepengurusan, dan jabatan, pembuatan dan penghapusan ikut dicatat — bukan hanya penyuntingan. Menghapus ranting membuat `members.ranting_id` anggotanya jadi null tanpa jejak lain, dan pada periode kepengurusan "kapan ia dibuat lalu dinonaktifkan" justru bagian dari arsipnya (B-9)
- **B-11** `riwayat_guru_besar` sengaja terpisah dan diisi manual, karena Guru Besar lama dari masa sebelum sistem ini ada tidak punya akun dan tidak bisa ditarik otomatis dari tabel `jabatan`
- **B-12** Format `nia`: **tahun bergabung + nomor urut empat digit**, contoh `2026-0001`. Nomor urut dihitung per tahun dan mulai lagi dari 1 tiap ganti tahun. Digenerate sistem saat status beranjak dari `pending` (B-1), tidak pernah diketik manual, dan tidak berubah lagi setelah diberikan
- **B-13** `no_warga`: **tepat 8 digit angka**, unik. Berbeda dari `nia`, nomor ini **tidak digenerate** — datanya berasal dari kartu tanda warga fisik yang sudah dimiliki, jadi **diisi manual oleh anggota sendiri** lewat halaman profilnya. Guru Besar dan Sekben tetap bisa mengisikannya juga (B-2 tidak dicabut). Hanya berlaku untuk `tingkat_keanggotaan = warga`
- **B-14** Sistem harus **selalu punya minimal satu Admin**. Dua pagar: (a) Admin tidak bisa mencabut `is_admin` miliknya sendiri — harus lewat Admin lain; (b) `is_admin` terakhir tidak bisa dicabut, dan akun Admin terakhir tidak bisa dihapus. Dijaga di level model supaya tidak ada jalur yang bisa melanggarnya, sebab sekali dilanggar tidak ada lagi yang berwenang mengatur hak akses dan pemulihannya harus lewat database langsung
- **B-15** Yang boleh membuka panel pengelola `/admin` adalah pengguna yang punya **minimal satu dari empat kolom hak akses** bernilai true (`is_editor`, `is_guru_besar`, `is_sekben`, `is_admin`). Anggota tanpa hak akses apa pun ditolak di pintu panel. Ini **gerbang masuk saja** — apa yang boleh dilakukan di dalamnya tetap ditentukan policy B-2, B-4, B-5, dan B-6, jadi Editor yang lolos masuk tetap tidak bisa mengubah sabuk atau hak akses siapa pun. Ditegakkan lewat `canAccessPanel()` di model `User`
- **B-16** Yang berhak mengelola `wilayah` dan `ranting`: **Guru Besar dan Sekben Umum saja** — sama seperti B-4, karena wilayah dan ranting adalah struktur organisasi yang sejenis dengan bagan kepengurusan. Admin tidak bisa, kecuali dia juga memegang salah satu bendera itu. Editor lolos gerbang B-15 dan boleh masuk panel, tapi tidak melihat menu wilayah/ranting sama sekali
- **B-17** Yang boleh **melihat** daftar dan rincian anggota di `/admin/anggota`: **semua yang lolos gerbang B-15**, yaitu pemegang minimal satu hak akses — termasuk Editor. Membaca sengaja dipisah tegas dari mengubah: B-2, B-5, dan B-6 mengatur siapa boleh MENGUBAH dan tidak ikut melonggar oleh aturan ini. Perlu disadari daftar ini memuat data pribadi seluruh anggota (nama, NIA, status, ranting)
- **B-18** **Menolak pendaftar.** Guru Besar dan Sekben Umum (sama seperti B-5) boleh menolak pendaftar yang masih `pending`. Tiga hal yang mengikat:
  - **Status akhirnya `ditolak`** — nilai enum baru pada `members.status`, bukan `non_aktif` maupun `alumni`. Dipisah karena keduanya berarti "pernah jadi anggota", sedangkan yang ditolak tidak pernah. Konsekuensinya A-12 butuh **pesan ketiga** di halaman masuk: akun `ditolak` tidak bisa masuk sama sekali, dan pesannya tidak boleh berbunyi "sedang tidak aktif" karena ia tidak pernah aktif. Sesudah ditolak ia kembali jadi pengunjung biasa — tetap bisa membuka beranda, artikel, dan galeri tanpa akun, tapi **namanya tidak muncul** di daftar Warga maupun Anggota di `/struktur` (fitur 09)
  - **NIA tidak boleh terbit untuknya.** Ini yang paling mudah terlewat: hook `saving` di model `Member` menerbitkan `nia` pada **setiap** perpindahan keluar dari `pending`, bukan hanya ke `aktif`. Kondisinya sekarang `if ($member->nia !== null || $member->status === 'pending') return;` — begitu statusnya jadi `ditolak`, syarat itu tidak lagi menahan dan nomor terbit untuk orang yang tidak pernah disetujui, bertentangan dengan B-1. **Hook-nya wajib ikut diubah** saat B-18 diimplementasikan, dan penolakan yang tidak menerbitkan NIA wajib punya tesnya sendiri
  - **Alasan penolakan dicatat di `audit_logs`**, lewat kolom `alasan` (text, nullable) yang ditambahkan ke tabel itu. Ditaruh di audit dan bukan di `members` supaya jejaknya permanen — baris audit tidak pernah diubah — dan supaya kolom yang sama bisa dipakai aksi lain yang butuh alasan, seperti pemutihan denda di fitur 04. Alasannya wajib diisi, tidak boleh kosong

  > **Belum diimplementasikan.** B-18 baru aturan; enum `ditolak`, kolom `audit_logs.alasan`, perubahan hook `nia`, pesan A-12 ketiga, dan penyaringan di `/struktur` semuanya belum ada di kode.

- **B-19** **Rantai atasan jabatan tidak boleh melingkar.** Sebuah jabatan tidak bisa jadi atasan dirinya sendiri, dan tidak bisa mengambil atasan dari bawahannya sendiri — ditelusuri sampai akar, bukan sekadar membandingkan tetangga langsung, sehingga A→B→A maupun rantai yang lebih panjang sama-sama ditolak. Alasannya bukan kerapian: bagan `/struktur` (fitur 09) menelusuri `parent_id` secara rekursif, dan satu lingkaran membuatnya berputar tanpa ujung. Pemilihan atasan juga dibatasi **satu periode** — atasan lintas periode tidak masuk akal karena bagan terikat masa bakti. Dijaga di model `Jabatan` supaya panel, tinker, dan seeder sama-sama tertutup
> **Jalur menolak pendaftar belum ada di kode.** Aturannya sudah ditulis di B-18, tapi belum diimplementasikan. Sampai itu dikerjakan, aksi "Ubah status" sengaja **tidak tampil** pada baris `pending` — memakai `non_aktif` sebagai penolakan dadakan akan menerbitkan NIA untuk orang yang tidak pernah disetujui (lihat alasannya di B-18). Pendaftar yang tidak dikehendaki dibiarkan `pending`; A-6 sudah menahannya di halaman masuk.

---

## Halaman pengelola

**`/admin/anggota`** — daftar anggota dengan filter tingkat, sabuk, status, ranting. Aksi: setujui pendaftar, ubah status, ubah tingkat & sabuk (khusus Guru Besar/Sekben), reset kata sandi.

**`/admin/periode`** dan **`/admin/jabatan`** — kelola periode kepengurusan dan jabatan, dikelompokkan sebagai "Struktur" di navigasi panel. Bagan disusun dengan memilih atasan (`parent_id`) tiap jabatan; pilihannya dibatasi satu periode dan tidak boleh melingkar (B-19).

**`/admin/wilayah`** — kelola wilayah dan ranting.

**`/admin/riwayat-guru-besar`** — kelola daftar Guru Besar historis untuk halaman About.

Panel pengelola boleh memakai Filament dan tidak wajib mengikuti `design-tokens.md`.

---

## Data awal (seeder)

```
Wilayah:  Kutai Barat, Samarinda
Ranting:  Melak Ulu (Kutai Barat), Samarinda Ulu (Samarinda)

Periode:  "Kepengurusan 2026-2027", 2026-2027, aktif

Jabatan contoh (susun berhierarki):
  Guru Besar                    parent: null
  ├─ Ketua Wilayah Kutai Barat  parent: Guru Besar
  │  └─ Ketua Ranting Melak Ulu parent: Ketua Wilayah Kutai Barat
  ├─ Ketua Wilayah Samarinda    parent: Guru Besar
  │  └─ Ketua Ranting Samarinda Ulu
  └─ Sekben Umum                parent: Guru Besar
```

Nama pemegang jabatan masih placeholder — beri komentar `// TODO: ganti dengan data pengurus resmi`.

Seeder dipecah menurut ketergantungannya, dan urutannya diatur di `DatabaseSeeder`:

| Seeder | Isi |
|---|---|
| `AkunUjiSeeder` | akun uji fitur 01 |
| `WilayahRantingSeeder` | wilayah dan ranting |
| `KeanggotaanUjiSeeder` | nia, tingkat, sabuk, dan ranting untuk akun uji |
| `StrukturSeeder` | periode kepengurusan dan bagan jabatan |

Daftar akun uji hidup di **satu tempat**: `AkunUjiSeeder::DAFTAR`. `KeanggotaanUjiSeeder` membaca daftar yang sama, jadi keduanya tidak pernah berbeda pendapat soal siapa akun ujinya dan bagaimana keadaan awalnya.

`WithoutModelEvents` dipasang di **tiap seeder**, bukan hanya di `DatabaseSeeder` — tiap seeder harus aman dijalankan sendirian, dan tanpa itu pemulihan meninggalkan baris audit palsu seolah ada pengurus yang mengubah data. `nia` diberikan manual oleh `KeanggotaanUjiSeeder` karena hook model sengaja tidak menyala.

**Seeder MEMULIHKAN, bukan sekadar melengkapi.** `php artisan db:seed` mengembalikan seluruh akun uji ke keadaan awalnya — status, `nia`, `no_warga`, `tanggal_naik_warga`, tingkat, sabuk, ranting, nama, hak akses, dan `harus_ganti_sandi` — **termasuk mengembalikan nilai ke null** bila keadaan awalnya null. Kalau hanya melengkapi yang kosong, akun uji yang terlanjur diubah lewat panel tidak pernah benar-benar kembali; itu yang sempat terjadi pada `pendingcoba1` — ia disetujui lewat panel, dan `nia`-nya terlanjur terbit padahal ia sengaja dibiarkan pending untuk menguji A-6.

Dua hal yang **tidak** disentuh seeder: akun di luar `AkunUjiSeeder::DAFTAR` (pendaftaran sungguhan lewat `/daftar` aman), dan `nia` yang sudah sah — B-12 menyebutnya tidak berubah lagi setelah diberikan, jadi nomornya tidak diacak ulang tiap seeding.

Dijaga `tests/Feature/SeederIdempotenTest.php`.

---

## Kriteria selesai

- [x] Semua migration di atas, dengan foreign key dan index yang benar
- [x] Model + relasi: `User hasOne Member`, `Member belongsTo Ranting`, `Ranting belongsTo Wilayah`, `Jabatan belongsTo Periode/User/Jabatan(parent)`, `Jabatan hasMany children`
- [x] Mutator `tingkatan` mengisi `tingkatan_urutan` otomatis
- [x] Penomoran `nia` sesuai B-12, diberikan otomatis saat pendaftar disetujui
- [x] Validasi `no_warga` sesuai B-13
- [x] Policy/gate sesuai B-2, B-4, B-5, B-6, dan pagar Admin B-14
- [x] Audit log tertulis untuk semua perubahan di B-10
- [x] Kerangka panel Filament di `/admin` dengan gerbang akses B-15
- [x] Panel kelola wilayah & ranting (B-16), dengan penolakan hapus induk yang terbaca
- [x] Panel anggota: daftar & lihat saja (B-17), tanpa aksi yang mengubah data
- [x] Panel anggota: aksi setujui pendaftar & ubah status (B-5)
- [x] Panel anggota: ubah tingkat & sabuk dan nomor warga (B-2), reset kata sandi (A-7)
- [ ] Panel anggota: ubah hak akses (B-6)
- [x] Panel kelola struktur: periode kepengurusan (B-8, B-9) dan jabatan (B-3, B-19)
- [ ] Panel kelola riwayat Guru Besar
- [x] Seeder data awal

## Skenario uji

1. Set `tingkatan = putih_warga_3` → `tingkatan_urutan` otomatis 6
2. Urutkan anggota berdasar `tingkatan_urutan` desc → Putih Warga 3 paling atas, Hitam/Polos paling bawah
3. Admin (tanpa jabatan Guru Besar/Sekben) mencoba ubah sabuk anggota → ditolak
4. Guru Besar mencoba ubah `is_admin` orang lain → ditolak
5. Anggota naik jadi Warga tanggal 1 → tidak muncul di penerbitan tagihan bulan itu, muncul bulan berikutnya
6. Tandai periode baru aktif → periode lama otomatis `aktif = false`, datanya tetap ada
7. Buat jabatan dengan `parent_id` menunjuk jabatan lain → bagan terbentuk benar sampai 3 tingkat
8. Isi `nama_jabatan` dengan teks apa pun ("Humas Ranting Melak") → diterima tanpa error validasi
9. Semua perubahan di B-10 → muncul di audit log dengan nilai sebelum dan sesudah
10. Pendaftar disetujui → dapat `nia` berformat `2026-0001`; orang kedua di tahun yang sama dapat `2026-0002`, dan yang bergabung tahun berikutnya mulai lagi dari `2027-0001`
11. Isi `no_warga` dengan 7 digit, 9 digit, ada spasi, atau ada huruf → ditolak; tepat 8 digit angka → diterima
12. Anggota mengisi `no_warga` miliknya sendiri → diterima; mengisi milik orang lain → ditolak
13. Admin mencabut `is_admin` miliknya sendiri → ditolak
14. Admin terakhir dicabut hak adminnya atau akunnya dihapus → ditolak, sistem tetap punya minimal satu Admin
15. Anggota tanpa satu pun hak akses membuka `/admin` → ditolak; Editor diterima; pengguna dengan lebih dari satu hak akses diterima (B-15)
16. Guru Besar dan Sekben membuka menu wilayah/ranting di panel → terlihat; Admin dan Editor → menunya tidak ada sama sekali (B-16)
17. Hapus wilayah yang masih punya ranting → ditolak dengan pesan yang terbaca, bukan galat SQL; wilayah yang sudah kosong → terhapus
18. Buat, sunting, dan hapus wilayah/ranting → ketiganya muncul di audit log (B-10)
19. Daftar anggota diurutkan bawaan menurut `tingkatan_urutan` menurun → Putih Warga 3 paling atas, Hitam/Polos paling bawah (lihat skenario 2)
20. Anggota `pending` tampil di daftar dengan NIA kosong yang terbaca wajar, bukan seperti data rusak (B-1)
21. Editor membuka `/admin/anggota` → boleh membaca (B-17); tapi tidak menemukan satu pun aksi yang mengubah data
22. Guru Besar/Sekben menyetujui pendaftar lewat panel → status jadi `aktif` dan NIA terbit lewat hook yang sama dengan jalur Tinker, melanjutkan deret tahun itu (B-5, B-12)
23. Admin dan Editor membuka `/admin/anggota` → daftarnya terbaca (B-17), tapi tombol "Setujui" dan "Ubah status" **tidak tampil sama sekali** (B-5)
24. Alumni dikembalikan jadi `aktif` → NIA lamanya dipakai lagi, tidak terbit yang baru (B-12: tidak berubah lagi setelah diberikan)
25. Baris `pending` → hanya punya aksi "Setujui"; "Ubah status" tidak tampil, dan `pending` tidak ada di pilihan statusnya
26. Ubah akun uji lewat panel atau tinker — status, nia, no_warga, tanggal naik warga, tingkat, sabuk — lalu jalankan `php artisan db:seed` → semuanya kembali ke keadaan awal, termasuk yang harus kembali null; akun di luar daftar akun uji tidak tersentuh
27. Jadikan sebuah jabatan atasan dirinya sendiri → ditolak; A→B lalu B dijadikan atasan A → ditolak; rantai lebih panjang (A→B→C lalu C dijadikan atasan A) → ditolak juga (B-19)
28. Pilihan atasan sebuah jabatan → hanya memuat jabatan sePeriode, tanpa dirinya sendiri dan tanpa seluruh bawahannya
29. Hapus periode yang masih punya jabatan lewat panel → notifikasi terbaca, bukan galat SQL; periode kosong → terhapus (B-9)
30. Hapus jabatan induk → bawahannya tetap ada dengan `parent_id` null, tidak ikut terhapus
