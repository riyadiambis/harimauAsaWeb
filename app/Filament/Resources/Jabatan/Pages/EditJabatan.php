<?php

namespace App\Filament\Resources\Jabatan\Pages;

use App\Filament\Resources\Jabatan\JabatanResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditJabatan extends EditRecord
{
    protected static string $resource = JabatanResource::class;

    /**
     * @return array<int, mixed>
     */
    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
