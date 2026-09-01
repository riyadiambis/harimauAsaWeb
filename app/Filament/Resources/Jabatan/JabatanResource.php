<?php

namespace App\Filament\Resources\Jabatan;

use App\Filament\Resources\Jabatan\Pages\CreateJabatan;
use App\Filament\Resources\Jabatan\Pages\EditJabatan;
use App\Filament\Resources\Jabatan\Pages\ListJabatan;
use App\Filament\Resources\Jabatan\Schemas\JabatanForm;
use App\Filament\Resources\Jabatan\Tables\JabatanTable;
use App\Models\Jabatan;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

/** Hak kelolanya B-4, ditegakkan JabatanPolicy. */
class JabatanResource extends Resource
{
    protected static ?string $model = Jabatan::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSquares2x2;

    protected static string|UnitEnum|null $navigationGroup = 'Struktur';

    protected static ?int $navigationSort = 2;

    protected static ?string $slug = 'jabatan';

    protected static ?string $modelLabel = 'jabatan';

    protected static ?string $pluralModelLabel = 'jabatan';

    protected static ?string $recordTitleAttribute = 'nama_jabatan';

    public static function form(Schema $schema): Schema
    {
        return JabatanForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return JabatanTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListJabatan::route('/'),
            'create' => CreateJabatan::route('/create'),
            'edit' => EditJabatan::route('/{record}/edit'),
        ];
    }
}
