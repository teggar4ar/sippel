<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\TahunAjaran;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Kelas>
 */
final class KelasFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tingkat_kelas' => fake()->randomElement([7, 8, 9]),
            'grup_kelas' => fake()->randomElement(['A', 'B', 'C', 'D']),
            'tahun_ajaran_id' => TahunAjaran::factory(),
            'wali_kelas_id' => User::factory(),
        ];
    }
}
