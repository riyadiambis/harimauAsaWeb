{{--
    BERANDA SEMENTARA — diganti UTUH di fitur 11.

    Beranda sungguhan (hero slider, pengumuman, artikel, galeri) dikerjakan di
    fitur 11. Berkas ini sengaja berdiri sendiri, tanpa komponen layout baru,
    supaya nanti bisa dibuang seluruhnya tanpa meninggalkan sisa.

    TODO fitur 11: ganti seluruh berkas ini dengan beranda publik sungguhan.

    Halaman ini PUBLIK. Tidak ada middleware auth di rutenya dan tidak boleh
    ditambahkan — sisi publik adalah separuh alasan aplikasi ini ada (PRD
    bagian 1 dan 3 nomor 4), bukan pelengkap di belakang login.

    Susunannya mengikuti pola yang sama dengan components/layout/auth.blade.php:
    padding halaman di <main>, dan <main> > <div> jadi kolom dengan jarak antar
    section 32px. Itu yang diperiksa tests/Browser/tokens.js.
--}}
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name') }}</title>

    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-canvas font-sans text-isi text-ink antialiased">
    {{-- Padding halaman 24px, mobile 16px. --}}
    <main class="flex min-h-screen flex-col px-4 py-10 sm:px-6">
        {{-- Lebar konten maksimal 1120px; jarak antar section 32px. --}}
        <div class="mx-auto flex w-full max-w-[1120px] flex-1 flex-col gap-8">
            <header class="flex justify-end">
                @guest
                    <a
                        href="{{ route('masuk') }}"
                        class="rounded-kontrol border border-line px-4 py-2 text-label text-ink transition-colors hover:bg-surface"
                    >
                        Masuk
                    </a>
                @else
                    <div class="flex items-center gap-3">
                        {{-- B-15 lewat User::punyaHakAkses() — pemeriksaan yang sama
                             dengan gerbang panelnya sendiri, bukan pemeriksaan kedua. --}}
                        @if (auth()->user()->punyaHakAkses())
                            <a
                                href="{{ url('/admin') }}"
                                class="rounded-kontrol border border-line px-4 py-2 text-label text-ink transition-colors hover:bg-surface"
                            >
                                Panel pengelola
                            </a>
                        @endif

                        <form method="POST" action="{{ route('keluar') }}">
                            @csrf
                            <button
                                type="submit"
                                class="cursor-pointer rounded-kontrol px-4 py-2 text-label text-ink-muted transition-colors hover:text-ink"
                            >
                                Keluar
                            </button>
                        </form>
                    </div>
                @endguest
            </header>

            {{-- Judul dan kalimatnya satu kesatuan, jadi jaraknya rapat —
                 32px di atas adalah jarak antar section, bukan antar baris. --}}
            <div class="flex flex-1 flex-col justify-center gap-4">
                <h1 class="text-judul-halaman text-brand">Pertalian Silat Harimau Asa</h1>

                <p class="max-w-[52ch] text-ink-muted">
                    Perguruan silat yang berdiri di Melak Ulu, Kutai Barat, sejak 2020.
                </p>
            </div>
        </div>
    </main>
</body>
</html>
