<?php

namespace App\Filament\Resources\Anggota\Pages;

use App\Filament\Resources\Anggota\AnggotaResource;
use Filament\Resources\Pages\ViewRecord;

class LihatAnggota extends ViewRecord
{
    protected static string $resource = AnggotaResource::class;

    /**
     * Tanpa EditAction. Aksi yang mengubah data dikerjakan sesi berikutnya.
     *
     * @return array<int, mixed>
     */
    protected function getHeaderActions(): array
    {
        return [];
    }
}
