# ROADMAP — Portal Harimau Asa

Buka file ini tiap mulai sesi kerja. Isinya urutan, ketergantungan, dan status tiap fitur.

**Status:** `belum` · `dikerjakan` · `selesai` · `perlu revisi`

---

## Urutan pengerjaan

| # | Fitur | File spek | Butuh | Status |
|---|---|---|---|---|
| 01 | Autentikasi & akun | `fitur/01-auth.md` | — | belum |
| 02 | Anggota, tingkatan & struktur | `fitur/02-anggota-struktur.md` | 01 | belum |
| 03 | Periode & penerbitan tagihan | `fitur/03-kas-periode.md` | 02 | belum |
| 04 | Jendela bayar & denda otomatis | `fitur/04-kas-denda.md` | 03 | belum |
| 05 | Pembayaran (kode unik, QRIS, bukti) | `fitur/05-kas-pembayaran.md` | 04 | belum |
| 06 | Verifikasi & audit log | `fitur/06-kas-verifikasi.md` | 05 | belum |
| 07 | Dashboard anggota | `fitur/07-dashboard.md` | 06 | belum |
| 08 | Kumpulan (dana insidental) | `fitur/08-kumpulan.md` | 06 | belum |
| 09 | Halaman publik: profil & struktur | `fitur/09-publik-profil.md` | 02 | belum |
| 10 | Konten: artikel & pengumuman | `fitur/10-konten.md` | 02 | belum |
| 11 | Beranda, hero slider & galeri | `fitur/11-beranda-galeri.md` | 10 | belum |
| 12 | Laporan & rekonsiliasi | `fitur/12-laporan.md` | 06, 08 | belum |
| 13 | LMS materi jurus | `fitur/13-lms.md` | 07 | belum |

**Sistem sudah bisa dipakai sungguhan setelah 07.** Sisanya pengembangan, bukan prasyarat.

File spek ditulis menjelang fitur itu dikerjakan, bukan sekaligus di awal — supaya isinya mencerminkan keadaan kode saat itu, bukan tebakan dari jauh hari. Yang sudah ditulis: 01, 02.

---

## Ringkasan tiap fitur

**01 — Autentikasi & akun.** Login pakai username (bukan email). Pendaftaran publik minta nama + username + kata sandi saja, masuk sebagai `pending` sampai disetujui. Reset kata sandi lewat sandi sementara dari Sekben Umum.

**02 — Anggota, tingkatan & struktur.** Tabel `members`, `wilayah`, `ranting`, `periode_kepengurusan`, `jabatan`, `riwayat_guru_besar`. Dua sumbu terpisah: tingkat keanggotaan (anggota/warga → penentu tagihan) dan hak akses (boolean: editor, guru besar, sekben, admin). Jabatan hierarkis dengan `parent_id` dan terikat periode.

**03 — Periode & penerbitan tagihan.** Periode kas per bulan. Job tanggal 1 pukul 00:05 menerbitkan tagihan untuk semua `warga` berstatus `aktif`. Idempoten.

**04 — Jendela bayar & denda otomatis.** Bebas denda tanggal 1–5. Job tanggal 6 menambah denda Rp1.000, maksimal 3 kali. Denda ditulis sebagai baris item baru, bukan mengubah nilai lama. Job melewati tagihan yang kode uniknya sedang aktif.

**05 — Pembayaran.** Anggota klik bayar → dapat kode unik 2 digit, berlaku 3 jam, membekukan nominal. Halaman bayar: QRIS statis, nominal dengan kode ditebalkan, nomor kantong Bank Jago + tombol salin, hitung mundur, unggah bukti.

**06 — Verifikasi & audit log.** Antrean Sekben Umum: bukti di kiri, data + flag peringatan di kanan. Setujui/Tolak dengan alasan. Semua keputusan masuk audit log.

**07 — Dashboard anggota.** Berbeda isi menurut tingkat. Warga: pita 12 bulan, tagihan kas aktif, LMS. Anggota: data diri, pengumuman internal, tagihan kumpulan. Keduanya menampilkan label jabatan & sabuk.

**08 — Kumpulan.** Pengumpulan dana insidental. Memakai ulang tabel `invoices` dengan `tipe = kumpulan`, sehingga alur pembayaran dan verifikasi sama persis dengan kas.

**09 — Halaman publik: profil & struktur.** `/profil` (About: sejarah, visi-misi, kolase foto, riwayat Guru Besar, tombol ke struktur) dan `/struktur` (bagan hierarki kepengurusan + daftar Warga + daftar Anggota).

**10 — Konten.** Artikel & pengumuman dalam satu tabel `posts`. Editor rich text dengan penyisipan gambar di tengah tulisan. Visibilitas publik/anggota.

**11 — Beranda, hero slider & galeri.** Carousel gambar otomatis tanpa teks overlay, section galeri di beranda, halaman `/galeri` dengan lightbox.

**12 — Laporan & rekonsiliasi.** Rekap per periode, daftar penunggak, ekspor CSV, halaman rekonsiliasi saldo kantong vs catatan sistem.

**13 — LMS materi jurus.** Khusus Warga. Pustaka video YouTube unlisted, tanpa progres. Kategori dikelola lewat tabel. Dikelola Guru Besar, Sekben Umum, dan Admin.

---

## Belum diputuskan

**Chat grup internal.** Ide: obrolan mirip WhatsApp untuk anggota yang sudah login. Belum masuk roadmap karena beban servernya perlu dipertimbangkan serius — chat realtime butuh koneksi persisten (WebSocket) yang jauh lebih berat daripada halaman biasa, dan mini PC rumahan akan terasa bebannya begitu banyak orang membuka aplikasi bersamaan. Alternatif ringan yang bisa dipertimbangkan nanti: papan diskusi (bukan realtime, cukup refresh biasa), atau tetap pakai WhatsApp untuk obrolan dan website untuk hal yang butuh catatan permanen. Diputuskan setelah 07 selesai dan beban server sesungguhnya terlihat.

---

## Kesepakatan yang berlaku lintas fitur

- Login pakai **username**, email opsional
- Yang kena kas bulanan: `tingkat_keanggotaan = warga` DAN `status = aktif`
- Anggota naik jadi Warga → mulai ditagih **bulan berikutnya**, bukan bulan berjalan
- `nia` dimiliki semua anggota sejak daftar; `no_warga` hanya diberikan saat naik ke tingkat Warga (nomor pada kartu tanda warga)
- Hak akses berupa **boolean terpisah**, bukan satu kolom role
- Bagan struktur di mobile ditampilkan sebagai **daftar bertingkat yang bisa di-expand**, bukan pohon bergaris
