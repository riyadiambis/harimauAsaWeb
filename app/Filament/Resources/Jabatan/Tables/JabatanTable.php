<?php

namespace App\Filament\Resources\Jabatan\Tables;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class JabatanTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('urutan')
            ->modifyQueryUsing(fn ($query) => $query->with(['periode', 'user', 'parent', 'ranting']))
            ->columns([
                TextColumn::make('nama_jabatan')
                    ->label('Jabatan')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('user.nama')
                    ->label('Pemegang')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('parent.nama_jabatan')
                    ->label('Atasan')
                    // Akar bagan memang tidak punya atasan; ini bukan data hilang.
                    ->placeholder('— puncak bagan'),

                TextColumn::make('periode.nama')
                    ->label('Periode')
                    ->sortable(),

                TextColumn::make('ranting.nama')
                    ->label('Ranting')
                    // Dipendekkan dari kalimat penuh: kolomnya memakan 156px dan
                    // mendorong tombol aksi keluar tepi tabel di 1440px.
                    ->placeholder('—'),

                TextColumn::make('urutan')
                    ->label('Urutan')
                    ->numeric()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('periode_id')
                    ->label('Periode')
                    ->relationship('periode', 'nama')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('ranting_id')
                    ->label('Ranting')
                    ->relationship('ranting', 'nama')
                    ->searchable()
                    ->preload(),
            ])
            // Tombol ikon: dengan 7 kolom, aksi berlabel teks terdorong keluar
            // tepi wadah tabel di 1440px.
            ->recordActions([
                EditAction::make()
                    ->iconButton()
                    ->tooltip('Ubah'),

                // Aturan hapus induk: bawahannya naik jadi akar lewat foreign key
                // nullOnDelete, tidak ikut terhapus. Tidak perlu pagar tambahan.
                DeleteAction::make()
                    ->iconButton()
                    ->tooltip('Hapus'),
            ])
            ->emptyStateHeading('Belum ada jabatan');
    }
}
