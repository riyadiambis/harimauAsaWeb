<?php

namespace App\Filament\Resources\Periode\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class PeriodeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('nama')
                ->label('Nama periode')
                ->placeholder('Kepengurusan 2026-2027')
                ->required()
                ->maxLength(255),

            TextInput::make('tahun_mulai')
                ->label('Tahun mulai')
                ->required()
                ->numeric()
                ->minValue(1900)
                ->maxValue(2200),

            TextInput::make('tahun_selesai')
                ->label('Tahun selesai')
                ->required()
                ->numeric()
                ->minValue(1900)
                ->maxValue(2200)
                ->gte('tahun_mulai'),

            Toggle::make('aktif')
                ->label('Periode aktif')
                ->helperText('Menyalakan ini otomatis menonaktifkan periode lain (B-8).'),
        ]);
    }
}
