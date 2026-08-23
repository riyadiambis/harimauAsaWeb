<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CekUsernameTest extends TestCase
{
    use RefreshDatabase;

    public function test_username_kosong_dilaporkan_tersedia(): void
    {
        $this->getJson('/cek-username?username=fikri_r')
            ->assertOk()
            ->assertExactJson(['username' => 'fikri_r', 'valid' => true, 'tersedia' => true]);
    }

    public function test_username_terpakai_dilaporkan_tidak_tersedia(): void
    {
        User::factory()->create(['username' => 'fikri_r']);

        $this->getJson('/cek-username?username=fikri_r')
            ->assertOk()
            ->assertJson(['valid' => true, 'tersedia' => false]);
    }

    /** Harus sepakat dengan unique index: baris soft-deleted masih memegang username. */
    public function test_username_milik_akun_terhapus_tidak_tersedia(): void
    {
        User::factory()->create(['username' => 'fikri_r'])->delete();

        $this->getJson('/cek-username?username=fikri_r')
            ->assertJson(['tersedia' => false]);
    }

    public function test_huruf_besar_dinormalkan_sebelum_dicek(): void
    {
        User::factory()->create(['username' => 'fikri_r']);

        $this->getJson('/cek-username?username=FIKRI_R')
            ->assertJson(['username' => 'fikri_r', 'tersedia' => false]);
    }

    public function test_username_tidak_sah_dilaporkan_tidak_valid(): void
    {
        foreach (['ran', 'rangga sap', 'rangga.sap', str_repeat('a', 21), ''] as $username) {
            $this->getJson('/cek-username?username='.urlencode($username))
                ->assertOk()
                ->assertJson(['valid' => false, 'tersedia' => false]);
        }
    }
}
