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
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $topics = [
            'Pengenalan Aljabar',
            'Membaca Teks Narasi',
            'Expressing Opinions',
            'Hukum Newton',
            'Sistem Pernapasan Manusia',
            'Perjuangan Kemerdekaan',
            'Peta dan Atlas',
            'Iman kepada Allah SWT',
            'Hak dan Kewajiban Warga Negara',
            'Permainan Bola Basket',
            'Seni Lukis',
            'Kerajinan Tangan',
        ];

        return [
            'tanggal' => fake()->dateTimeBetween('-1 month', 'now'),
            'topik' => fake()->randomElement($topics),
            'catatan' => fake()->optional(0.3)->sentence(),
            'kelas_id' => Kelas::factory(),
            'mata_pelajaran_id' => MataPelajaran::factory(),
            'guru_id' => User::factory(),
            'presensi_mandiri' => false,
            'durasi_presensi_menit' => null,
        ];
    }

    /**
     * Indicate that this activity has QR self-attendance enabled
     */
    public function withQrAttendance(int $duration = 10): static
    {
        return $this->state(fn (array $attributes): array => [
            'presensi_mandiri' => true,
            'durasi_presensi_menit' => $duration,
        ]);
    }
}
