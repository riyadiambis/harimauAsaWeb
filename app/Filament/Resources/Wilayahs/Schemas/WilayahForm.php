<?php

namespace App\Filament\Resources\Wilayahs\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class WilayahForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('nama')
                    ->label('Nama wilayah')
                    ->required()
                    ->maxLength(255),

                TextInput::make('urutan')
                    ->label('Urutan tampil')
                    ->helperText('Angka kecil tampil lebih dulu.')
                    ->required()
                    ->numeric()
                    ->default(0),
            ]);
    }
}
