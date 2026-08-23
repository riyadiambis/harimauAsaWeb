<x-layout.auth judul="Masuk">
    <form method="POST" action="{{ route('masuk') }}">
        @csrf

        <x-kartu>
            <div class="flex flex-col gap-1">
                <h1 class="text-judul-halaman text-ink">Masuk</h1>
                <p class="text-isi text-ink-muted">Portal anggota dan kas perguruan.</p>
            </div>

            {{-- Pesan A-5, A-6, status tidak aktif, dan batas percobaan semuanya
                 bersifat form, bukan per kolom — jadi ditampilkan sekali di atas. --}}
            @if ($errors->any())
                <div class="rounded-kontrol bg-state-late/12 px-3.5 py-3">
                    <p class="text-isi text-state-late">{{ $errors->first() }}</p>
                </div>
            @endif

            <div class="flex flex-col gap-5">
                <x-kolom
                    nama="username"
                    label="Username"
                    nilai="{{ old('username') }}"
                    autocomplete="username"
                    autocapitalize="none"
                    autofocus
                    :galat="false"
                />

                <x-kolom
                    nama="password"
                    label="Kata sandi"
                    tipe="password"
                    autocomplete="current-password"
                    :galat="false"
                />

                <label class="flex items-center gap-2.5 text-isi text-ink">
                    <input
                        type="checkbox"
                        name="ingat_saya"
                        value="1"
                        class="size-4 accent-action"
                        @checked(old('ingat_saya'))
                    >
                    Ingat saya
                </label>
            </div>

            <x-tombol>Masuk</x-tombol>
        </x-kartu>
    </form>

    <div class="flex flex-col gap-2 text-center">
        <p class="text-isi text-ink-muted">
            Belum punya akun?
            <a href="{{ route('daftar') }}" class="text-brand underline underline-offset-4 hover:text-ink">Daftar</a>
        </p>
        <p class="text-label text-ink-faint">Lupa kata sandi? Hubungi pengurus.</p>
    </div>
</x-layout.auth>
