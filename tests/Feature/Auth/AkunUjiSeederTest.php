<?php

namespace Tests\Feature\Auth;

use App\Models\Member;
use App\Models\User;
use Database\Seeders\AkunUjiSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AkunUjiSeederTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<int, array{0: string, 1: string, 2: string|null, 3: string}>
     */
    public static function akun(): array
    {
        return [
            ['adminmin', 'adminamin123', 'is_admin', 'aktif'],
            ['gurubesar', 'gurusuhu212', 'is_guru_besar', 'aktif'],
            ['sekbenuang', 'uangUang123', 'is_sekben', 'aktif'],
            ['editorcoba1', 'editedit1', 'is_editor', 'aktif'],
            ['wargacoba1', 'wargawarga1', null, 'aktif'],
            ['anggotacoba1', 'anggotaanggota1', null, 'aktif'],
            ['pendingcoba1', 'pendingpending1', null, 'pending'],
        ];
    }

    #[DataProvider('akun')]
    public function test_akun_uji_dibuat_dengan_sandi_hak_akses_dan_status_yang_benar(
        string $username,
        string $sandi,
        ?string $hakAkses,
        string $status,
    ): void {
        $this->seed(AkunUjiSeeder::class);

        $user = User::where('username', $username)->firstOrFail();

        $this->assertTrue(Hash::check($sandi, $user->password), "sandi {$username} tidak cocok");
        $this->assertSame($status, $user->member->status);

        foreach (['is_admin', 'is_guru_besar', 'is_sekben', 'is_editor'] as $kolom) {
            $this->assertSame($kolom === $hakAkses, $user->{$kolom}, "{$kolom} pada {$username}");
        }
    }

    public function test_seeder_aman_dijalankan_dua_kali(): void
    {
        $this->seed(AkunUjiSeeder::class);
        $this->seed(AkunUjiSeeder::class);

        $this->assertSame(7, User::count());
        $this->assertSame(7, Member::count());
    }

    /** A-6 butuh satu akun pending untuk diuji. */
    public function test_hanya_satu_akun_yang_pending(): void
    {
        $this->seed(AkunUjiSeeder::class);

        $this->assertSame(1, Member::where('status', 'pending')->count());
        $this->assertSame(6, Member::where('status', 'aktif')->count());
    }
}
