@props(['judul'])

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $judul }} — Portal Harimau Asa</title>

    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-canvas font-sans text-isi text-ink antialiased">
    {{-- min-h-screen + justify-center: kartu di tengah saat muat, dan wadahnya
         ikut memanjang (bukan terpotong) kalau form lebih tinggi dari layar. --}}
    <main class="flex min-h-screen flex-col justify-center px-4 py-10 sm:px-6">
        <div class="mx-auto flex w-full max-w-[420px] flex-col gap-8">
            <p class="text-center text-judul-bagian text-brand">Pertalian Silat Harimau Asa</p>

            {{ $slot }}
        </div>
    </main>
</body>
</html>
