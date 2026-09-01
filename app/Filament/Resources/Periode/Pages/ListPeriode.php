<?php

namespace App\Filament\Resources\Periode\Pages;

use App\Filament\Resources\Periode\PeriodeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPeriode extends ListRecords
{
    protected static string $resource = PeriodeResource::class;

    /**
     * @return array<int, mixed>
     */
    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
