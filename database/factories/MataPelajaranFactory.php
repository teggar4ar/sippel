<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Kelas;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\MataPelajaran>
 */
final class MataPelajaranFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $subjects = [
            'Matematika',
            'Bahasa Indonesia',
            'Bahasa Inggris',
            'IPA (Fisika)',
            'IPA (Biologi)',
            'IPS (Sejarah)',
            'IPS (Geografi)',
            'Pendidikan Agama Islam',
            'PKn',
            'Penjaskes',
            'Seni Budaya',
            'Prakarya',
        ];

        return [
            'nama_mapel' => fake()->randomElement($subjects),
            'guru_id' => User::factory(),
            'kelas_id' => Kelas::factory(),
        ];
    }
}
