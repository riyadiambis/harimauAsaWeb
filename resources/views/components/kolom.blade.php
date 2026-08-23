@props([
    'nama',
    'label',
    'tipe' => 'text',
    'nilai' => null,
    'keterangan' => null,
    'galat' => true,
])

{{-- Jarak antar elemen pakai gap di parent, bukan margin per anak. --}}
<div class="flex flex-col gap-2">
    <label for="{{ $nama }}" class="text-label text-ink-muted">{{ $label }}</label>

    <input
        id="{{ $nama }}"
        name="{{ $nama }}"
        type="{{ $tipe }}"
        @if ($tipe !== 'password') value="{{ $nilai }}" @endif
        {{-- Fokus hanya mengubah warna border jadi --ink-muted. Tanpa ring: dua
             lapis garis membuat kolom ber-autofocus terlihat jauh lebih berat
             daripada sisa halaman, dan itu melawan gaya soft minimalism. --}}
        {{ $attributes->merge([
            'class' => 'w-full rounded-kontrol border border-line bg-surface px-3.5 py-2.5 text-isi text-ink placeholder:text-ink-faint focus:border-ink-muted focus:outline-none',
        ]) }}
    >

    {{ $slot }}

    @if ($keterangan)
        <p class="text-label text-ink-muted">{{ $keterangan }}</p>
    @endif

    @if ($galat)
        @error($nama)
            <p class="text-label text-state-late">{{ $message }}</p>
        @enderror
    @endif
</div>
