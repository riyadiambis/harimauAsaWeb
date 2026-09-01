<?php

namespace App\Filament\Resources\Rantings\Pages;

use App\Filament\Resources\Rantings\RantingResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditRanting extends EditRecord
{
    protected static string $resource = RantingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
