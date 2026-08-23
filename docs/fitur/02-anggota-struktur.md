# 02 — Anggota, tingkatan & struktur

**Status:** belum · **Butuh:** 01-auth · **Berikutnya:** 03-kas-periode

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

```
members
  user_id                FK users, unique
  nia                    string, unique          -- nomor induk anggota, semua orang
  no_warga               string, nullable, unique -- nomor kartu tanda warga,
                                                  -- hanya untuk tingkat warga
  tingkat_keanggotaan    enum(anggota, warga), default anggota
  tingkatan              enum(hitam_polos, kuning, oren,
                              merah_warga_1, merah_warga_2, putih_warga_3)
  tingkatan_urutan       tinyint  -- 1..6, lihat tabel di bawah
  ranting_id             FK ranting, nullable
  tanggal_gabung         date
  tanggal_naik_warga     date, nullable
  status                 enum(pending, aktif, non_aktif, alumni), default pending
  iuran_override         integer, nullable
  timestamps

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
  id, nama, tahun_mulai, tahun_selesai, foto nullable,
  keterangan nullable, urutan, timestamps
  -- diisi manual, TIDAK diambil otomatis dari tabel jabatan
```

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
- **B-10** Semua perubahan pada B-2, B-4, B-5, dan B-6 wajib menulis audit log (pelaku, waktu, nilai sebelum, nilai sesudah)
- **B-11** `riwayat_guru_besar` sengaja terpisah dan diisi manual, karena Guru Besar lama dari masa sebelum sistem ini ada tidak punya akun dan tidak bisa ditarik otomatis dari tabel `jabatan`

---

## Halaman pengelola

**`/admin/anggota`** — daftar anggota dengan filter tingkat, sabuk, status, ranting. Aksi: setujui pendaftar, ubah status, ubah tingkat & sabuk (khusus Guru Besar/Sekben), reset kata sandi.

**`/admin/struktur`** — kelola periode kepengurusan dan jabatan. Bagan bisa disusun dengan memilih atasan (`parent_id`) tiap jabatan.

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

---

## Kriteria selesai

- [ ] Semua migration di atas, dengan foreign key dan index yang benar
- [ ] Model + relasi: `User hasOne Member`, `Member belongsTo Ranting`, `Ranting belongsTo Wilayah`, `Jabatan belongsTo Periode/User/Jabatan(parent)`, `Jabatan hasMany children`
- [ ] Mutator `tingkatan` mengisi `tingkatan_urutan` otomatis
- [ ] Policy/gate sesuai B-2, B-4, B-5, B-6
- [ ] Audit log tertulis untuk semua perubahan di B-10
- [ ] Panel kelola anggota, struktur, wilayah, riwayat Guru Besar
- [ ] Seeder data awal

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
