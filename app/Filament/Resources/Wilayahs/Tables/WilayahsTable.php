<?php

namespace App\Filament\Resources\Wilayahs\Tables;

use App\Models\Wilayah;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class WilayahsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('urutan')
            ->columns([
                TextColumn::make('nama')
                    ->label('Nama')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('ranting_count')
                    ->label('Jumlah ranting')
                    ->counts('ranting')
                    ->sortable(),

                TextColumn::make('urutan')
                    ->label('Urutan')
                    ->numeric()
                    ->sortable(),
            ])
            ->recordActions([
                EditAction::make(),

                // Aturan hapus induk: wilayah yang masih punya ranting ditolak.
                //
                // Diperiksa di sini supaya pengurus mendapat kalimat, bukan
                // SQLSTATE[23000]. Pagar sesungguhnya tetap ada dua lapis di
                // belakangnya — hook deleting di model Wilayah dan foreign key
                // restrictOnDelete — jadi jalur mana pun tetap tertutup.
                DeleteAction::make()
                    ->before(function (Wilayah $record, DeleteAction $action): void {
                        $jumlah = $record->ranting()->count();

                        if ($jumlah === 0) {
                            return;
                        }

                        Notification::make()
                            ->danger()
                            ->title('Wilayah ini tidak bisa dihapus')
                            ->body("\"{$record->nama}\" masih punya {$jumlah} ranting. Pindahkan atau hapus rantingnya lebih dulu.")
                            ->persistent()
                            ->send();

                        // cancel(), bukan halt(): halt() menahan modal
                        // konfirmasi tetap terbuka sehingga pengurus melihat
                        // notifikasi penolakan sekaligus pertanyaan "yakin?"
                        // yang sudah tidak ada gunanya dijawab.
                        $action->cancel();
                    }),
            ]);

        // Penghapusan massal sengaja tidak dipasang: satu wilayah bertautan
        // ditolak akan menggagalkan seluruh batch, dan pesannya jadi tidak jelas
        // menunjuk baris yang mana.
    }
}
