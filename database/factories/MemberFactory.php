<?php

namespace Database\Factories;

use App\Models\Member;
use App\Models\Ranting;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Member>
 */
class MemberFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'status' => 'pending',
            'tingkat_keanggotaan' => 'anggota',
            // Lewat mutator, jadi tingkatan_urutan ikut terisi.
            'tingkatan' => 'hitam_polos',
            'tanggal_gabung' => fake()->dateTimeBetween('-3 years')->format('Y-m-d'),
        ];
    }

    /**
     * Anggota yang pendaftarannya sudah disetujui pengurus.
     */
    public function aktif(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'aktif',
        ]);
    }

    /**
     * Tingkat Warga — yang kena tagihan kas bulanan.
     */
    public function warga(): static
    {
        return $this->state(fn (array $attributes) => [
            'tingkat_keanggotaan' => 'warga',
            'tingkatan' => 'merah_warga_1',
            'tanggal_naik_warga' => fake()->dateTimeBetween('-2 years')->format('Y-m-d'),
        ]);
    }

    public function sabuk(string $tingkatan): static
    {
        return $this->state(fn (array $attributes) => [
            'tingkatan' => $tingkatan,
        ]);
    }

    public function diRanting(Ranting $ranting): static
    {
        return $this->state(fn (array $attributes) => [
            'ranting_id' => $ranting->id,
        ]);
    }
}
