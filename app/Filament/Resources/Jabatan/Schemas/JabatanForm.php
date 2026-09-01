<?php

namespace App\Filament\Resources\Jabatan\Schemas;

use App\Models\Jabatan;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class JabatanForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('periode_id')
                ->label('Periode kepengurusan')
                ->relationship('periode', 'nama')
                ->required()
                ->searchable()
                ->preload()
                // Mengganti periode membuat atasan yang sudah dipilih jadi tidak
                // sah, karena atasan wajib satu periode.
                ->live()
                ->afterStateUpdated(fn (Select $component) => $component
                    ->getContainer()
                    ->getComponent('parent_id')
                    ?->state(null)),

            Select::make('user_id')
                ->label('Pemegang jabatan')
                ->relationship('user', 'nama')
                ->required()
                ->searchable()
                ->preload(),

            // B-3: teks bebas. Tidak ada enum, daftar pilihan, maupun validasi
            // terhadap daftar apa pun — hanya panjang maksimum kolomnya.
            TextInput::make('nama_jabatan')
                ->label('Nama jabatan')
                ->placeholder('Ketua Ranting Melak Ulu')
                ->required()
                ->maxLength(255),

            Select::make('parent_id')
                ->key('parent_id')
                ->label('Atasan di bagan')
                ->placeholder('Tanpa atasan (akar bagan)')
                ->searchable()
                ->preload()
                ->options(fn (?Jabatan $record, callable $get): array => Jabatan::query()
                    ->where('periode_id', $get('periode_id'))
                    // B-19: dirinya sendiri dan seluruh bawahannya tidak boleh
                    // jadi atasan, karena itu menutup lingkaran.
                    ->when($record, fn (Builder $q) => $q->whereIn('id', $record->idCalonAtasan()))
                    ->orderBy('urutan')
                    ->pluck('nama_jabatan', 'id')
                    ->all())
                ->helperText('Hanya jabatan dalam periode yang sama. Kosongkan untuk jabatan puncak.'),

            Select::make('ranting_id')
                ->label('Ranting')
                ->relationship('ranting', 'nama')
                ->placeholder('Tidak terikat ranting')
                ->searchable()
                ->preload(),

            TextInput::make('urutan')
                ->label('Urutan tampil')
                ->helperText('Angka kecil tampil lebih dulu.')
                ->required()
                ->numeric()
                ->default(0),
        ]);
    }
}
