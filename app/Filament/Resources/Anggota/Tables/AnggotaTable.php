<?php

namespace App\Filament\Resources\Anggota\Tables;

use App\Models\Member;
use App\Models\Ranting;
use Filament\Actions\ViewAction;
use Filament\Support\Enums\FontFamily;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class AnggotaTable
{
    public static function configure(Table $table): Table
    {
        return $table
            // Skenario uji 2: urutan bawaan memakai tingkatan_urutan, BUKAN enum
            // `tingkatan`. Kolom itu ada persis untuk ini — mengurutkan enum di
            // MySQL mengikuti urutan deklarasi, yang tidak dijamin sama dengan
            // urutan sabuk dan diam-diam berubah kalau enumnya disusun ulang.
            ->defaultSort('tingkatan_urutan', 'desc')
            // Nama dan ranting dibaca dari relasi; tanpa ini tiap baris memicu
            // query sendiri.
            ->modifyQueryUsing(fn ($query) => $query->with(['user', 'ranting']))
            ->columns([
                TextColumn::make('user.nama')
                    ->label('Nama')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('nia')
                    ->label('NIA')
                    // design-tokens: JetBrains Mono khusus angka, kode unik, NIA,
                    // dan no warga. Panel memakai --mono-font-family, yang
                    // diarahkan ke JetBrains Mono di AdminPanelProvider.
                    ->fontFamily(FontFamily::Mono)
                    ->searchable()
                    ->sortable()
                    // B-1: nia null selama pendaftar masih pending. Placeholder
                    // ini yang membuatnya terbaca sebagai keadaan yang wajar,
                    // bukan seperti kolom yang gagal terisi.
                    ->placeholder('Belum terbit'),

                TextColumn::make('no_warga')
                    ->label('No. warga')
                    ->fontFamily(FontFamily::Mono)
                    ->searchable()
                    // B-13: hanya berlaku untuk tingkat warga, jadi kosong pada
                    // sebagian besar baris. Disembunyikan secara bawaan supaya
                    // tabel tidak penuh kolom kosong.
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->placeholder('—'),

                TextColumn::make('tingkat_keanggotaan')
                    ->label('Tingkat')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => Member::LABEL_TINGKAT_KEANGGOTAAN[$state] ?? $state)
                    ->color(fn (string $state): string => $state === 'warga' ? 'warning' : 'gray')
                    ->sortable(),

                TextColumn::make('tingkatan')
                    ->label('Sabuk')
                    // Peta label diambil dari model, yang isinya persis tabel
                    // "Peta tingkatan sabuk" di spek.
                    ->formatStateUsing(fn (string $state): string => Member::LABEL_TINGKATAN[$state] ?? $state)
                    // Diurutkan lewat kolom turunannya, bukan enumnya.
                    ->sortable(query: fn ($query, string $direction) => $query->orderBy('tingkatan_urutan', $direction)),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => Member::LABEL_STATUS[$state] ?? $state)
                    ->color(fn (string $state): string => match ($state) {
                        'aktif' => 'success',
                        'pending' => 'warning',
                        'non_aktif' => 'danger',
                        'alumni' => 'gray',
                        default => 'gray',
                    })
                    ->sortable(),

                TextColumn::make('ranting.nama')
                    ->label('Ranting')
                    ->searchable()
                    ->sortable()
                    ->placeholder('Belum ditentukan'),
            ])
            ->filters([
                SelectFilter::make('tingkat_keanggotaan')
                    ->label('Tingkat keanggotaan')
                    ->options(Member::LABEL_TINGKAT_KEANGGOTAAN),

                SelectFilter::make('tingkatan')
                    ->label('Tingkatan sabuk')
                    ->options(Member::LABEL_TINGKATAN),

                SelectFilter::make('status')
                    ->label('Status')
                    ->options(Member::LABEL_STATUS),

                SelectFilter::make('ranting_id')
                    ->label('Ranting')
                    ->relationship('ranting', 'nama')
                    ->searchable()
                    ->preload(),
            ])
            // Satu-satunya aksi: melihat. Tidak ada yang mengubah data.
            ->recordActions([
                ViewAction::make(),
            ])
            ->toolbarActions([])
            ->emptyStateHeading('Belum ada anggota')
            ->emptyStateDescription('Anggota muncul di sini begitu ada yang mendaftar lewat halaman daftar.');
    }
}
