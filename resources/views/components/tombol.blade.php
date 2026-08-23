{{-- Tombol utama: latar --action, teks --surface, tanpa border. --}}
<button
    {{ $attributes->merge([
        'type' => 'submit',
        'class' => 'w-full rounded-kontrol bg-action px-4 py-2.5 text-isi font-medium text-surface transition-colors hover:bg-action-hover focus-visible:ring-1 focus-visible:ring-ink-muted focus-visible:ring-offset-2 focus-visible:ring-offset-surface focus-visible:outline-none',
    ]) }}
>
    {{ $slot }}
</button>
