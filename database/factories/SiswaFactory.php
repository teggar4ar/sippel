<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Kelas;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Siswa>
 */
final class SiswaFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nis' => fake('id_ID')->unique()->numerify('#####'),
            'user_id' => User::factory(),
            'kelas_id' => Kelas::factory(),
        ];
    }
}
