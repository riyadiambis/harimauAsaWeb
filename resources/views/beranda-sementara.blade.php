{{--
    HALAMAN SEMENTARA — bukan beranda.

    Beranda sungguhan (hero slider, galeri, konten) dikerjakan di fitur 11, dan
    dasbor anggota di fitur 07. Sampai keduanya ada, pengguna yang sudah masuk
    tapi tidak memegang hak akses apa pun tidak punya tempat untuk mendarat —
    halaman ini yang menahannya supaya tidak terlihat seperti aplikasi rusak.

    TODO fitur 07: ganti dengan dasbor anggota.
    TODO fitur 11: rute / diambil alih beranda publik.
--}}
<x-layout.auth judul="Beranda">
    <x-kartu>
        <div class="flex flex-col gap-2">
            <h1 class="text-judul-halaman text-ink">Kamu sudah masuk</h1>
            <p class="text-ink-muted">
                Dasbor anggota belum tersedia. Pengurus akan mengabari lewat
                WhatsApp kalau ada yang perlu kamu lakukan.
            </p>
        </div>

        <form method="POST" action="{{ route('keluar') }}">
            @csrf
            <x-tombol>Keluar</x-tombol>
        </form>
    </x-kartu>
</x-layout.auth>
