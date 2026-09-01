<?php

namespace App\Filament\Resources\Jabatan\Pages;

use App\Filament\Resources\Jabatan\JabatanResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListJabatan extends ListRecords
{
    protected static string $resource = JabatanResource::class;

    /**
     * @return array<int, mixed>
     */
    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
