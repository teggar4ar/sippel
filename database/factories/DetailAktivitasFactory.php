<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\Keaktifan;
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
            'keaktifan' => $kehadiran === 'hadir'
                ? fake()->randomElement([
                    Keaktifan::Aktif->value,
                    Keaktifan::Aktif->value,
                    Keaktifan::Aktif->value,
                    Keaktifan::SangatAktif->value,
                    Keaktifan::SangatAktif->value,
                    Keaktifan::Cukup->value,
                    Keaktifan::Cukup->value,
                    Keaktifan::Cukup->value,
                    Keaktifan::Pasif->value,
                ])
                : null,
            'catatan' => fake()->optional(0.3)->sentence(),
            'aktivitas_pembelajaran_id' => AktivitasPembelajaran::factory(),
            'siswa_id' => Siswa::factory(),
        ];
    }
}
