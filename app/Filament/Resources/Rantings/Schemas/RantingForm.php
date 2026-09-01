<?php

namespace App\Filament\Resources\Rantings\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class RantingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('wilayah_id')
                    ->label('Wilayah')
                    ->relationship('wilayah', 'nama')
                    ->required()
                    ->searchable()
                    ->preload(),

                TextInput::make('nama')
                    ->label('Nama ranting')
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
