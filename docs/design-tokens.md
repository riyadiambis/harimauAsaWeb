# Design tokens — Portal Harimau Asa

Sumber kebenaran tampilan. Baca sebelum menyentuh view, komponen, atau CSS apa pun.

Gaya: **soft minimalism** — netral hangat, kontras rendah, ruang kosong lega, sudut membulat, bayangan nyaris tak terlihat.

---

## Warna

```css
--canvas:       #F2F1ED;  /* latar halaman (greige hangat, bukan putih) */
--surface:      #FBFAF8;  /* permukaan kartu */
--surface-alt:  #EAE8E2;  /* kartu sekunder, sidebar, area kosong, skeleton */
--line:         #E2E0D9;  /* garis pemisah dan border (0.5–1px) */
--ink:          #26251F;  /* teks utama (hitam kehangatan, bukan #000) */
--ink-muted:    #6E6C64;  /* teks sekunder, label, keterangan */
--ink-faint:    #9B998F;  /* placeholder, teks nonaktif */

--brand:        #7A3B33;  /* oxblood — identitas perguruan, dipakai HEMAT */
--action:       #2F2E28;  /* tombol utama (charcoal) */
--action-hover: #45443C;

--state-paid:   #4A6B52;  /* lunas */
--state-wait:   #8A6D3B;  /* menunggu verifikasi */
--state-late:   #8C3B34;  /* telat / kena denda */
--state-none:   #B4B2A9;  /* belum jatuh tempo */
```

**Aturan:** halaman didominasi netral. `--brand` maksimal muncul 4–5 titik per halaman (logo, label section, tautan, aksen linimasa). Tanpa gradien, tanpa glow, tanpa bayangan tebal.

**Judul halaman memakai `--ink`, bukan `--brand`.** Daftar di atas tidak menyebut judul halaman, dan itu disengaja — judul yang berwarna brand membuat setiap halaman punya titik oxblood besar, persis kebalikan dari "dipakai hemat".

Satu pengecualian: bila judulnya sendiri **adalah nama perguruan** ("Pertalian Silat Harimau Asa" di beranda), ia dihitung sebagai logo/identitas dan boleh `--brand`. Judul yang menamai isi halaman — "Anggota", "Struktur kepengurusan", "Galeri" — tetap `--ink`.

Badge status: warna status di atas latar terang, dibuat dengan opacity 12% di atas `--surface`.

---

## Tipografi

- **Plus Jakarta Sans** — judul dan teks isi (buatan Tokotype, studio Indonesia)
- **JetBrains Mono** — khusus angka rupiah, kode unik, NIA, no warga, dan tanggal di tabel

| Peran | Ukuran | Berat |
|---|---|---|
| Judul halaman | 28px | 600 |
| Judul bagian | 20px | 600 |
| Judul kartu | 16px | 500 |
| Teks isi | 15px | 400 |
| Label & keterangan | 13px | 400 |
| Nominal besar | 24px mono | 500 |

Line-height teks isi 1.7. Judul pakai **sentence case**, bukan Title Case.

---

## Tata letak

- Lebar konten maksimal 1120px, padding halaman 24px (mobile 16px)
- Radius: kartu 16px, tombol & input 10px, badge 6px
- Jarak antar section 32px, antar kartu 16px, padding dalam kartu 24px
- Sidebar zona anggota 240px, latar `--surface-alt`
- Bayangan maksimal `0 1px 3px rgba(38,37,31,0.06)`

**Tombol**
- Utama: latar `--action`, teks `--surface`, tanpa border
- Sekunder: transparan, border `--line`, teks `--ink`
- Input: latar `--surface`, border `--line`, fokus border `--ink-muted` (bukan biru sistem)

---

## Adaptasi mobile

- Desktop 1440px, mobile 390px. Responsif mulai 360px
- Sidebar → hamburger di top bar, muncul sebagai drawer dari samping
- Semua grid → satu kolom vertikal
- Tabel lebar → kartu vertikal, atau horizontal scroll khusus di area tabel saja
- **Bagan struktur** → daftar bertingkat (indent) yang bisa di-expand, BUKAN pohon bergaris. Pohon 3 tingkat tidak muat di 390px

---

## Pola komponen

### Kartu
`padding: 24px; border-radius: 16px; background: var(--surface); border: 1px solid var(--line);`
Tanpa ikon dekoratif, tanpa bayangan tebal.

### Kartu dengan avatar + teks
`display: flex; flex-direction: column; gap: 8px;`
Avatar 48px bulat, latar `--surface-alt`, isi inisial.
Jarak antar elemen pakai `gap` di parent — JANGAN margin per anak.
Tidak ada ikon lain di dalam kartu ini selain avatar.

### Badge status
`padding: 4px 10px; border-radius: 6px; font-size: 13px; font-weight: 500;`
Empat varian: lunas (`--state-paid`), menunggu (`--state-wait`), telat (`--state-late`), belum jatuh tempo (`--state-none`).

### Badge tingkatan sabuk
Sama seperti badge status, tapi warnanya netral (`--surface-alt` + teks `--ink-muted`) supaya tidak bersaing dengan status pembayaran.

### Pita 12 bulan
Elemen tanda pengenal aplikasi ini. Kerjakan dengan rapi.
- Desktop: flex row, gap 6px, kotak 24×24px, radius 6px, 12 kotak Jan–Des dalam satu baris
- Mobile: grid 6 kolom × 2 baris
- Warna kotak mengikuti empat status di atas
- Hover/tap menampilkan nama bulan dan status

---

## Larangan

Ini yang paling sering dilanggar dan paling merusak hasil:

1. **Ikon dekoratif besar sebagai pengisi ruang kosong.** Kalau kartu terasa kosong, perbesar padding atau perkecil kartu — jangan tambah ikon. Ikon hanya boleh muncul kalau spesifikasi menyebut ukurannya eksplisit
2. **Warna di luar daftar di atas**
3. **Bayangan lebih tebal dari yang ditentukan**, gradien, glow
4. **Title Case pada judul**
5. **Lorem ipsum atau placeholder abu-abu** di halaman yang mau ditinjau — pakai konten contoh yang realistis

---

## Yang membuat tampilan terasa dirancang, bukan hasil generator

Tiga hal ini sengaja tidak simetris. Jangan "dirapikan" jadi grid biasa:

1. **Kolase foto di halaman About** — tiga foto bertumpuk dengan sedikit rotasi dan tumpang tindih, bukan tiga kolom sejajar
2. **Linimasa sejarah** — bentuk lingkaran berpanah mendatar, bukan daftar bullet
3. **Pita 12 bulan** — deretan kotak status yang tidak ada di template mana pun

Selebihnya: konten asli mengalahkan segalanya. Satu foto latihan sungguhan mengubah kesan halaman lebih besar daripada semua token di dokumen ini.

---

## Verifikasi (wajib setelah mengubah tampilan)

Pakai Playwright:
1. Screenshot viewport 1440px dan 390px
2. Periksa: ada elemen tumpang tindih? ada ikon yang tidak diminta? jarak antar section 32px? warna di luar daftar? kontras teks cukup?
3. Perbaiki, screenshot ulang
4. Ulangi sampai bersih — baru lapor
