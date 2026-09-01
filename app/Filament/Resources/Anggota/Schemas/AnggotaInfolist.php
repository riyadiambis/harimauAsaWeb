<?php

namespace App\Filament\Resources\Anggota\Schemas;

use App\Models\Member;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontFamily;

/**
 * Rincian anggota, baca saja. Tidak ada satu pun entri yang bisa disunting di
 * sini — penyuntingan dikerjakan sesi berikutnya dengan policy dan audit
 * lognya masing-masing.
 */
class AnggotaInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Identitas')
                ->columns(2)
                ->schema([
                    TextEntry::make('user.nama')
                        ->label('Nama lengkap'),

                    TextEntry::make('nia')
                        ->label('NIA')
                        ->fontFamily(FontFamily::Mono)
                        // B-1: null selama pendaftar masih pending.
                        ->placeholder('Belum terbit — pendaftaran belum disetujui'),

                    TextEntry::make('no_warga')
                        ->label('Nomor warga')
                        ->fontFamily(FontFamily::Mono)
                        // B-13: hanya untuk tingkat warga.
                        ->placeholder('Belum ada'),

                    TextEntry::make('status')
                        ->label('Status keanggotaan')
                        ->badge()
                        ->formatStateUsing(fn (string $state): string => Member::LABEL_STATUS[$state] ?? $state)
                        ->color(fn (string $state): string => match ($state) {
                            'aktif' => 'success',
                            'pending' => 'warning',
                            'non_aktif' => 'danger',
                            default => 'gray',
                        }),
                ]),

            Section::make('Tingkat & sabuk')
                ->columns(2)
                ->schema([
                    TextEntry::make('tingkat_keanggotaan')
                        ->label('Tingkat keanggotaan')
                        ->badge()
                        ->formatStateUsing(fn (string $state): string => Member::LABEL_TINGKAT_KEANGGOTAAN[$state] ?? $state)
                        ->color(fn (string $state): string => $state === 'warga' ? 'warning' : 'gray')
                        ->helperText('Penentu kewajiban kas bulanan.'),

                    TextEntry::make('tingkatan')
                        ->label('Tingkatan sabuk')
                        ->formatStateUsing(fn (string $state): string => Member::LABEL_TINGKATAN[$state] ?? $state),

                    TextEntry::make('ranting.nama')
                        ->label('Ranting')
                        ->placeholder('Belum ditentukan'),

                    TextEntry::make('ranting.wilayah.nama')
                        ->label('Wilayah')
                        ->placeholder('Belum ditentukan'),
                ]),

            Section::make('Tanggal')
                ->columns(2)
                ->schema([
                    TextEntry::make('tanggal_gabung')
                        ->label('Tanggal gabung')
                        ->date('j F Y'),

                    TextEntry::make('tanggal_naik_warga')
                        ->label('Tanggal naik warga')
                        // B-7: terisi hanya setelah naik ke tingkat warga.
                        ->date('j F Y')
                        ->placeholder('Belum naik warga'),
                ]),
        ]);
    }
}
