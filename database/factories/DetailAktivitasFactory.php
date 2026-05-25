<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\AktivitasPembelajaran;
use App\Models\Siswa;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\DetailAktivitas>
 */
final class DetailAktivitasFactory extends Factory
{
    public function definition(): array
    {
        $kehadiran = fake()->randomElement([
            'hadir', 'hadir', 'hadir', 'hadir', 'hadir', 'hadir',
            'izin', 'izin',
            'sakit',
            'alpa',
        ]);

        return [
            'kehadiran' => $kehadiran,
            'partisipasi' => $kehadiran === 'hadir'
                ? fake()->randomElement([3, 3, 3, 4, 4, 2, 2, 2, 1])
                : null,
            'nilai' => $kehadiran === 'hadir'
                ? fake()->numberBetween(50, 100)
                : null,
            'catatan' => fake()->optional(0.3)->sentence(),
            'aktivitas_pembelajaran_id' => AktivitasPembelajaran::factory(),
            'siswa_id' => Siswa::factory(),
        ];
    }
}
