<?php

namespace App\Filament\Resources\Anggota\Tables;

use App\Models\Member;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Support\Enums\FontFamily;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class AnggotaTable
{
    /**
     * Pilihan status untuk aksi "Ubah status" (B-5).
     *
     * `pending` sengaja TIDAK ada di sini. Status itu hanya bisa ditinggalkan
     * lewat aksi "Setujui pendaftar", dan tidak ada jalan kembali ke sana —
     * spek tidak punya jalur menolak pendaftar, jadi tidak dikarang di sini.
     *
     * @return array<string, string>
     */
    private static function pilihanStatus(): array
    {
        return array_diff_key(Member::LABEL_STATUS, ['pending' => null]);
    }

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
            ->recordActions([
                ViewAction::make(),

                // --- B-5: menyetujui pendaftar -------------------------------
                //
                // Aksinya hanya menggeser status. NIA diterbitkan hook `saving`
                // di model Member (B-12), dan baris auditnya ditulis trait
                // MencatatAudit (B-10) — keduanya jalur yang sama dengan yang
                // dipakai Tinker dan seeder. Tidak ada logika penomoran maupun
                // pencatatan yang ditulis ulang di sini; kalau ada, ia akan
                // menyimpang diam-diam begitu aturannya berubah.
                Action::make('setujui')
                    ->label('Setujui')
                    ->icon(null)
                    ->color('success')
                    // Policy B-5 sudah memuat syarat status === 'pending', jadi
                    // tombolnya hilang sendiri begitu pendaftarnya disetujui.
                    // Aksi yang tidak diizinkan TIDAK TAMPIL, bukan tampil lalu
                    // gagal saat diklik.
                    ->visible(fn (Member $record): bool => auth()->user()?->can('setujui', $record) ?? false)
                    ->requiresConfirmation()
                    ->modalHeading('Setujui pendaftar ini?')
                    ->modalDescription('Statusnya menjadi aktif dan NIA-nya terbit. NIA tidak berubah lagi setelah diberikan.')
                    ->modalSubmitActionLabel('Setujui')
                    ->action(function (Member $record): void {
                        $record->status = 'aktif';
                        $record->save();

                        Notification::make()
                            ->success()
                            ->title('Pendaftar disetujui')
                            ->body("NIA {$record->fresh()->nia} diterbitkan untuk {$record->user->nama}.")
                            ->send();
                    }),

                // --- B-5: mengubah status ------------------------------------
                Action::make('ubahStatus')
                    ->label('Ubah status')
                    ->icon(null)
                    ->visible(fn (Member $record): bool => $record->status !== 'pending'
                        && (auth()->user()?->can('ubahStatus', Member::class) ?? false))
                    ->modalHeading('Ubah status keanggotaan')
                    ->modalSubmitActionLabel('Simpan')
                    ->fillForm(fn (Member $record): array => ['status' => $record->status])
                    ->schema([
                        Select::make('status')
                            ->label('Status')
                            ->options(self::pilihanStatus())
                            ->required()
                            ->native(false)
                            // A-12: keduanya menutup akses masuk, jadi pengurus
                            // perlu tahu akibatnya sebelum menyimpan.
                            ->helperText('Non-aktif dan alumni sama-sama menutup akses masuk (A-12).'),
                    ])
                    ->action(function (Member $record, array $data): void {
                        $sebelum = $record->status;
                        $record->status = $data['status'];
                        $record->save();

                        Notification::make()
                            ->success()
                            ->title('Status diperbarui')
                            ->body(sprintf(
                                '%s: %s → %s.',
                                $record->user->nama,
                                Member::LABEL_STATUS[$sebelum] ?? $sebelum,
                                $record->labelStatus(),
                            ))
                            ->send();
                    }),
            ])
            ->toolbarActions([])
            ->emptyStateHeading('Belum ada anggota')
            ->emptyStateDescription('Anggota muncul di sini begitu ada yang mendaftar lewat halaman daftar.');
    }
}
