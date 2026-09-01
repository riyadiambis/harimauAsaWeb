<?php

namespace App\Filament\Resources\Rantings\Tables;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class RantingsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('urutan')
            ->columns([
                TextColumn::make('wilayah.nama')
                    ->label('Wilayah')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('nama')
                    ->label('Nama')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('members_count')
                    ->label('Anggota')
                    ->counts('members')
                    ->sortable(),

                TextColumn::make('urutan')
                    ->label('Urutan')
                    ->numeric()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('wilayah_id')
                    ->label('Wilayah')
                    ->relationship('wilayah', 'nama')
                    ->searchable()
                    ->preload(),
            ])
            ->recordActions([
                EditAction::make(),

                // Ranting boleh dihapus. Anggotanya tidak ikut terhapus —
                // members.ranting_id jadi null (aturan hapus induk). Karena
                // perubahan itu tidak meninggalkan jejak lain, penghapusan
                // ranting ditulis ke audit log oleh model.
                DeleteAction::make(),
            ]);
    }
}
