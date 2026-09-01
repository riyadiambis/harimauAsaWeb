<?php

namespace App\Filament\Resources\Periode;

use App\Filament\Resources\Periode\Pages\CreatePeriode;
use App\Filament\Resources\Periode\Pages\EditPeriode;
use App\Filament\Resources\Periode\Pages\ListPeriode;
use App\Filament\Resources\Periode\Schemas\PeriodeForm;
use App\Filament\Resources\Periode\Tables\PeriodeTable;
use App\Models\PeriodeKepengurusan;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

/** Hak kelolanya B-4, ditegakkan PeriodeKepengurusanPolicy. */
class PeriodeResource extends Resource
{
    protected static ?string $model = PeriodeKepengurusan::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static string|UnitEnum|null $navigationGroup = 'Struktur';

    protected static ?int $navigationSort = 1;

    protected static ?string $slug = 'periode';

    protected static ?string $modelLabel = 'periode kepengurusan';

    protected static ?string $pluralModelLabel = 'periode kepengurusan';

    protected static ?string $recordTitleAttribute = 'nama';

    public static function form(Schema $schema): Schema
    {
        return PeriodeForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PeriodeTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPeriode::route('/'),
            'create' => CreatePeriode::route('/create'),
            'edit' => EditPeriode::route('/{record}/edit'),
        ];
    }
}
