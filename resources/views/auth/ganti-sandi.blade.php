<x-layout.auth judul="Ganti kata sandi">
    <form method="POST" action="{{ route('ganti-sandi.update') }}">
        @csrf
        @method('PUT')

        <x-kartu>
            <div class="flex flex-col gap-1">
                <h1 class="text-judul-halaman text-ink">Ganti kata sandi</h1>
                <p class="text-isi text-ink-muted">
                    Kata sandimu baru direset pengurus. Buat kata sandi sendiri dulu
                    sebelum melanjutkan.
                </p>
            </div>

            <div class="flex flex-col gap-5">
                <x-kolom
                    nama="password"
                    label="Kata sandi baru"
                    tipe="password"
                    autocomplete="new-password"
                    keterangan="Minimal 8 karakter."
                    autofocus
                />

                <x-kolom
                    nama="password_confirmation"
                    label="Ulangi kata sandi baru"
                    tipe="password"
                    autocomplete="new-password"
                />
            </div>

            <x-tombol>Simpan kata sandi</x-tombol>
        </x-kartu>
    </form>

    {{-- Jalan keluar kalau ternyata yang masuk akun orang lain. --}}
    <form method="POST" action="{{ route('keluar') }}" class="text-center">
        @csrf
        <button type="submit" class="text-label text-ink-faint underline underline-offset-4 hover:text-ink">
            Keluar
        </button>
    </form>
</x-layout.auth>
