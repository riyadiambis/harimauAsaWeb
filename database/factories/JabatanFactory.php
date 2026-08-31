<?php

namespace Database\Factories;

use App\Models\Jabatan;
use App\Models\PeriodeKepengurusan;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Jabatan>
 */
class JabatanFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'periode_id' => PeriodeKepengurusan::factory(),
            'user_id' => User::factory(),
            // B-3: teks bebas, jadi contohnya pun tidak diambil dari daftar tetap.
            'nama_jabatan' => 'Ketua '.fake()->citySuffix().' '.fake()->firstName(),
            'parent_id' => null,
            'ranting_id' => null,
            'urutan' => 0,
        ];
    }

    public function dibawah(Jabatan $atasan): static
    {
        return $this->state(fn (array $attributes) => [
            'parent_id' => $atasan->id,
            'periode_id' => $atasan->periode_id,
        ]);
    }
}
