<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Kelas;
use App\Models\MataPelajaran;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AktivitasPembelajaran>
 */
final class AktivitasPembelajaranFactory extends Factory
{
    public function definition(): array
    {
        return [
            'tanggal' => fake()->dateTimeBetween('-30 days', 'now')->format('Y-m-d'),
            'topik' => fake()->sentence(3),
            'catatan' => fake()->optional(0.7)->paragraph(),
            'kelas_id' => Kelas::factory(),
            'mata_pelajaran_id' => MataPelajaran::factory(),
            'guru_id' => User::factory(),
        ];
    }
}
