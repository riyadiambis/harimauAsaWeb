<?php

namespace Database\Factories;

use App\Models\RiwayatGuruBesar;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RiwayatGuruBesar>
 */
class RiwayatGuruBesarFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $mulai = fake()->numberBetween(1960, 2015);

        return [
            'nama' => fake()->name(),
            'tahun_mulai' => $mulai,
            'tahun_selesai' => $mulai + fake()->numberBetween(3, 15),
            'foto' => null,
            'keterangan' => null,
            'urutan' => 0,
        ];
    }

    /**
     * Guru Besar yang sedang menjabat — belum punya tahun selesai.
     */
    public function menjabat(): static
    {
        return $this->state(fn (array $attributes) => ['tahun_selesai' => null]);
    }
}
