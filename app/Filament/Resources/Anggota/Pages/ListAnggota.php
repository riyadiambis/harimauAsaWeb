<?php

namespace App\Filament\Resources\Anggota\Pages;

use App\Filament\Resources\Anggota\AnggotaResource;
use Filament\Resources\Pages\ListRecords;

class ListAnggota extends ListRecords
{
    protected static string $resource = AnggotaResource::class;

    /**
     * Tanpa CreateAction: anggota lahir dari pendaftaran publik (A-2), tidak
     * pernah dibuat manual dari panel.
     *
     * @return array<int, mixed>
     */
    protected function getHeaderActions(): array
    {
        return [];
    }
}
