<x-layout.auth judul="Pendaftaran terkirim">
    <x-kartu>
        <div class="flex flex-col gap-2">
            <h1 class="text-judul-halaman text-ink">Pendaftaran terkirim</h1>
            <p class="text-isi text-ink">
                Pendaftaran kamu sudah masuk. Pengurus akan meninjau dan menghubungimu.
            </p>
        </div>

        <p class="text-isi text-ink-muted">
            Akunmu belum bisa dipakai masuk sampai disetujui. Kalau lebih dari beberapa hari
            belum ada kabar, tanyakan langsung ke pengurus ranting.
        </p>
    </x-kartu>

    <p class="text-center text-isi text-ink-muted">
        <a href="{{ route('masuk') }}" class="text-brand underline underline-offset-4 hover:text-ink">
            Kembali ke halaman masuk
        </a>
    </p>
</x-layout.auth>
