<?php

namespace Database\Factories;

use App\Models\PeriodeKepengurusan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PeriodeKepengurusan>
 */
class PeriodeKepengurusanFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $mulai = fake()->numberBetween(2015, 2030);

        return [
            'nama' => "Kepengurusan {$mulai}-".($mulai + 1),
            'tahun_mulai' => $mulai,
            'tahun_selesai' => $mulai + 1,
            'aktif' => false,
        ];
    }

    public function aktif(): static
    {
        return $this->state(fn (array $attributes) => ['aktif' => true]);
    }
}
