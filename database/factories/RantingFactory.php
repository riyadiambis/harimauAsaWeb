<?php

namespace Database\Factories;

use App\Models\Ranting;
use App\Models\Wilayah;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Ranting>
 */
class RantingFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'wilayah_id' => Wilayah::factory(),
            'nama' => fake()->unique()->citySuffix().' '.fake()->firstName(),
            'urutan' => 0,
        ];
    }
}
