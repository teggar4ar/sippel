<?php

declare(strict_types=1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TahunAjaran>
 */
final class TahunAjaranFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $year = fake()->year();

        return [
            'nama_tahun' => $year . '/' . ($year + 1),
            'semester' => fake()->randomElement(['Ganjil', 'Genap']),
            'tanggal_mulai' => fake()->dateTimeBetween('-1 year', 'now'),
            'tanggal_selesai' => fake()->dateTimeBetween('now', '+1 year'),
            'status' => false,
        ];
    }

    /**
     * Indicate that this is the active academic year.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => true,
        ]);
    }
}
