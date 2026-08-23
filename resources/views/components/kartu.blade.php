{{-- Kartu: padding 24px, radius 16px, --surface, border --line, bayangan tipis. --}}
<div {{ $attributes->merge(['class' => 'flex flex-col gap-6 rounded-kartu border border-line bg-surface p-6 shadow-kartu']) }}>
    {{ $slot }}
</div>
