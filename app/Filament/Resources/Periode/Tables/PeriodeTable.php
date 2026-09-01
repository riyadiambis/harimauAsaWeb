<?php

namespace App\Filament\Resources\Periode\Tables;

use App\Models\PeriodeKepengurusan;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Support\Enums\FontFamily;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PeriodeTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('tahun_mulai', 'desc')
            ->columns([
                TextColumn::make('nama')
                    ->label('Nama')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('tahun_mulai')
                    ->label('Mulai')
                    ->fontFamily(FontFamily::Mono)
                    ->sortable(),

                TextColumn::make('tahun_selesai')
                    ->label('Selesai')
                    ->fontFamily(FontFamily::Mono)
                    ->sortable(),

                // IconColumn boolean merender "Yes"/"No" yang tidak ikut
                // diterjemahkan. B-9 juga membuat "Arsip" lebih tepat daripada
                // sekadar "tidak aktif".
                TextColumn::make('aktif')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (bool $state): string => $state ? 'Aktif' : 'Arsip')
                    ->color(fn (bool $state): string => $state ? 'success' : 'gray')
                    ->sortable(),

                TextColumn::make('jabatan_count')
                    ->label('Jumlah jabatan')
                    ->counts('jabatan')
                    ->sortable(),
            ])
            ->recordActions([
                EditAction::make()
                    ->iconButton()
                    ->tooltip('Ubah'),

                DeleteAction::make()
                    ->iconButton()
                    ->tooltip('Hapus')
                    ->before(function (PeriodeKepengurusan $record, DeleteAction $action): void {
                        $jumlah = $record->jabatan()->count();

                        if ($jumlah === 0) {
                            return;
                        }

                        Notification::make()
                            ->danger()
                            ->title('Periode ini tidak bisa dihapus')
                            ->body("\"{$record->nama}\" masih punya {$jumlah} jabatan. Hapus jabatannya lebih dulu, atau cukup nonaktifkan periodenya — periode lama memang disimpan sebagai arsip (B-9).")
                            ->persistent()
                            ->send();

                        // cancel(), bukan halt(): modal konfirmasi ikut tertutup.
                        $action->cancel();
                    }),
            ])
            ->emptyStateHeading('Belum ada periode kepengurusan');
    }
}
