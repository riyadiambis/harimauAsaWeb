<?php

namespace App\Filament\Resources\Wilayahs;

use App\Filament\Resources\Wilayahs\Pages\CreateWilayah;
use App\Filament\Resources\Wilayahs\Pages\EditWilayah;
use App\Filament\Resources\Wilayahs\Pages\ListWilayahs;
use App\Filament\Resources\Wilayahs\Schemas\WilayahForm;
use App\Filament\Resources\Wilayahs\Tables\WilayahsTable;
use App\Models\Wilayah;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

/**
 * B-16: hak aksesnya ditegakkan WilayahPolicy, yang ditemukan Filament otomatis
 * lewat konvensi nama. Editor lolos gerbang panel B-15 tapi viewAny() menolaknya
 * di sini, jadi menunya tidak muncul sama sekali untuk dia.
 */
class WilayahResource extends Resource
{
    protected static ?string $model = Wilayah::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMap;

    protected static string|UnitEnum|null $navigationGroup = 'Wilayah & ranting';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'wilayah';

    protected static ?string $pluralModelLabel = 'wilayah';

    protected static ?string $recordTitleAttribute = 'nama';

    public static function form(Schema $schema): Schema
    {
        return WilayahForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return WilayahsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListWilayahs::route('/'),
            'create' => CreateWilayah::route('/create'),
            'edit' => EditWilayah::route('/{record}/edit'),
        ];
    }
}
