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
    public function definition(): array
    {
        $subjects = [
            'Matematika', 'Bahasa Indonesia', 'Bahasa Inggris',
            'IPA', 'IPS', 'Pendidikan Agama Islam',
            'PKN', 'Seni Budaya', 'PJOK', 'Prakarya', 'TIK',
        ];

        return [
            'nama_mapel' => fake()->randomElement($subjects),
            'guru_id' => User::factory(),
            'kelas_id' => Kelas::factory(),
        ];
    }
}
