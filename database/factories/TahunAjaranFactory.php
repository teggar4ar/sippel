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
        // Use sequence to ensure unique combinations
        static $counter = 0;
        $counter++;

        $baseYear = 2020 + $counter;
        $semester = $counter % 2 === 0 ? 'Ganjil' : 'Genap';

        return [
            'nama_tahun' => $baseYear.'/'.($baseYear + 1),
            'semester' => $semester,
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
