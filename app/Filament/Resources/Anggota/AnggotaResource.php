<?php

namespace App\Filament\Resources\Anggota;

use App\Filament\Resources\Anggota\Pages\LihatAnggota;
use App\Filament\Resources\Anggota\Pages\ListAnggota;
use App\Filament\Resources\Anggota\Schemas\AnggotaInfolist;
use App\Filament\Resources\Anggota\Tables\AnggotaTable;
use App\Models\Member;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

/**
 * Daftar dan rincian anggota. HANYA BACA.
 *
 * Tidak ada halaman buat maupun sunting, dan tidak ada satu pun aksi yang
 * mengubah data — setujui pendaftar, ubah status, ubah tingkat & sabuk, serta
 * reset kata sandi dikerjakan terpisah supaya masing-masing bisa dijaga policy
 * dan audit lognya sendiri (B-2, B-5, B-6, B-10).
 *
 * Hak bacanya B-17: semua yang lolos gerbang panel B-15, termasuk Editor.
 * Ditegakkan MemberPolicy::viewAny() dan view().
 */
class AnggotaResource extends Resource
{
    protected static ?string $model = Member::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static ?int $navigationSort = 0;

    /** Spek menyebut /admin/anggota, bukan /admin/members. */
    protected static ?string $slug = 'anggota';

    protected static ?string $modelLabel = 'anggota';

    protected static ?string $pluralModelLabel = 'anggota';

    protected static ?string $recordTitleAttribute = 'nia';

    /**
     * Judul dan remah roti memakai nama orangnya, bukan `nia`.
     *
     * `nia` dipertahankan sebagai recordTitleAttribute karena itu yang dicari
     * orang di pencarian global, tapi memakainya sebagai judul membuat halaman
     * pendaftar `pending` berjudul kosong — B-1: mereka belum punya nia.
     */
    public static function getRecordTitle(?Model $record): ?string
    {
        return $record?->user?->nama;
    }

    public static function table(Table $table): Table
    {
        return AnggotaTable::configure($table);
    }

    public static function infolist(Schema $schema): Schema
    {
        return AnggotaInfolist::configure($schema);
    }

    public static function getPages(): array
    {
        // Sengaja tanpa 'create' dan 'edit'.
        return [
            'index' => ListAnggota::route('/'),
            'view' => LihatAnggota::route('/{record}'),
        ];
    }

    /**
     * Pagar tambahan di atas policy. MemberPolicy memang tidak punya create(),
     * update(), maupun delete() sehingga Gate sudah menolaknya, tapi menyatakan
     * ini eksplisit membuat sifat baca-saja resource ini tidak bergantung pada
     * ketiadaan sebuah metode.
     */
    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(mixed $record): bool
    {
        return false;
    }

    public static function canDelete(mixed $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }
}
