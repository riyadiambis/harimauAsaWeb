<?php

namespace App\Filament\Resources\Rantings\Pages;

use App\Filament\Resources\Rantings\RantingResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListRantings extends ListRecords
{
    protected static string $resource = RantingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
