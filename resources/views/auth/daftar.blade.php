<x-layout.auth judul="Daftar">
    <form method="POST" action="{{ route('daftar') }}">
        @csrf

        <x-kartu>
            <div class="flex flex-col gap-1">
                <h1 class="text-judul-halaman text-ink">Daftar</h1>
                <p class="text-isi text-ink-muted">
                    Pengurus akan meninjau pendaftaranmu sebelum akun bisa dipakai.
                </p>
            </div>

            <div class="flex flex-col gap-5">
                <x-kolom
                    nama="nama"
                    label="Nama lengkap"
                    nilai="{{ old('nama') }}"
                    autocomplete="name"
                    autofocus
                />

                {{-- A-1: ketersediaan dicek sambil mengetik, tidak menunggu submit. --}}
                <div
                    x-data="{
                        nilai: @js(old('username', '')),
                        status: 'diam',
                        pesan: {
                            memeriksa: 'Memeriksa…',
                            tersedia: 'Username tersedia.',
                            terpakai: 'Username ini sudah dipakai. Coba yang lain.',
                            tidak_valid: 'Gunakan 4–20 karakter: huruf kecil, angka, dan garis bawah.',
                        },
                        async periksa() {
                            const u = this.nilai.trim().toLowerCase();

                            if (u === '') {
                                this.status = 'diam';
                                return;
                            }

                            this.status = 'memeriksa';

                            try {
                                const respons = await fetch(
                                    '{{ route('cek-username') }}?username=' + encodeURIComponent(u),
                                    { headers: { Accept: 'application/json' } }
                                );
                                const data = await respons.json();

                                this.status = data.tersedia
                                    ? 'tersedia'
                                    : (data.valid ? 'terpakai' : 'tidak_valid');
                            } catch (e) {
                                // Gagal menghubungi server bukan alasan menghalangi submit —
                                // validasi di server tetap jadi penentu akhir.
                                this.status = 'diam';
                            }
                        },
                    }"
                >
                    <x-kolom
                        nama="username"
                        label="Username"
                        autocomplete="username"
                        autocapitalize="none"
                        x-model="nilai"
                        x-on:input.debounce.500ms="periksa()"
                    >
                        <p
                            x-cloak
                            x-show="status !== 'diam'"
                            x-text="pesan[status]"
                            :class="{
                                'text-ink-faint': status === 'memeriksa',
                                'text-state-paid': status === 'tersedia',
                                'text-state-late': status === 'terpakai' || status === 'tidak_valid',
                            }"
                            class="text-label"
                        ></p>
                    </x-kolom>
                </div>

                <x-kolom
                    nama="password"
                    label="Kata sandi"
                    tipe="password"
                    autocomplete="new-password"
                    keterangan="Minimal 8 karakter."
                />

                <x-kolom
                    nama="password_confirmation"
                    label="Ulangi kata sandi"
                    tipe="password"
                    autocomplete="new-password"
                />
            </div>

            <x-tombol>Daftar</x-tombol>
        </x-kartu>
    </form>

    <p class="text-center text-isi text-ink-muted">
        Sudah punya akun?
        <a href="{{ route('masuk') }}" class="text-brand underline underline-offset-4 hover:text-ink">Masuk</a>
    </p>
</x-layout.auth>
