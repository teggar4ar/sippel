<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\AktivitasPembelajaran;
use App\Models\DetailAktivitas;
use App\Models\Kelas;
use App\Models\MataPelajaran;
use App\Models\Siswa;
use App\Models\SiswaKelasHistory;
use App\Models\TahunAjaran;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

final class ScenarioSeeder extends Seeder
{
    public function run(): void
    {
        if (User::where('email', 'teacher1@sippel.test')->exists()) {
            $this->command->info('Scenario data already seeded. Skipping ScenarioSeeder.');

            return;
        }

        DB::transaction(function (): void {
            $tahunAjaran = $this->seedTahunAjaran();
            $teachers = $this->seedTeachers();
            $classes = $this->seedClasses($tahunAjaran, $teachers);
            $subjects = $this->seedSubjects($classes, $teachers);
            $students = $this->seedStudents($classes, $tahunAjaran);
            $activities = $this->seedActivities($subjects, $classes, $teachers, $tahunAjaran);
            $this->seedDetailActivities($activities, $students);
        });
    }

    private function seedTahunAjaran(): TahunAjaran
    {
        return TahunAjaran::create([
            'nama_tahun' => '2025/2026',
            'semester' => 'Genap',
            'status' => true,
            'tanggal_mulai' => '2026-01-01',
            'tanggal_selesai' => '2026-06-01',
        ]);
    }

    /**
     * @return array{0: User, 1: User}
     */
    private function seedTeachers(): array
    {
        $teacher1 = User::firstOrCreate(
            ['email' => 'teacher1@sippel.test'],
            [
                'name' => fake('id_ID')->name(),
                'password' => Hash::make('password'),
                'jenis_kelamin' => fake()->randomElement(['L', 'P']),
                'email_verified_at' => now(),
            ],
        );
        $teacher1->assignRole('teacher');

        $teacher2 = User::firstOrCreate(
            ['email' => 'teacher2@sippel.test'],
            [
                'name' => fake('id_ID')->name(),
                'password' => Hash::make('password'),
                'jenis_kelamin' => fake()->randomElement(['L', 'P']),
                'email_verified_at' => now(),
            ],
        );
        $teacher2->assignRole('teacher');

        return [$teacher1, $teacher2];
    }

    /**
     * @param  User[]  $teachers
     * @return array{0: Kelas, 1: Kelas}
     */
    private function seedClasses(TahunAjaran $tahunAjaran, array $teachers): array
    {
        $kelas7A = Kelas::create([
            'tingkat_kelas' => 7,
            'grup_kelas' => 'A',
            'wali_kelas_id' => $teachers[0]->id,
            'tahun_ajaran_id' => $tahunAjaran->id,
        ]);

        $kelas8A = Kelas::create([
            'tingkat_kelas' => 8,
            'grup_kelas' => 'A',
            'wali_kelas_id' => $teachers[1]->id,
            'tahun_ajaran_id' => $tahunAjaran->id,
        ]);

        return [$kelas7A, $kelas8A];
    }

    /**
     * @param  Kelas[]  $classes
     * @param  User[]  $teachers
     * @return array<string, MataPelajaran>
     */
    private function seedSubjects(array $classes, array $teachers): array
    {
        $subjectMap = [
            ['nama_mapel' => 'Matematika', 'kelas' => 0, 'guru' => 0],
            ['nama_mapel' => 'Bahasa Indonesia', 'kelas' => 0, 'guru' => 0],
            ['nama_mapel' => 'IPA', 'kelas' => 0, 'guru' => 0],
            ['nama_mapel' => 'Bahasa Inggris', 'kelas' => 1, 'guru' => 1],
            ['nama_mapel' => 'IPS', 'kelas' => 1, 'guru' => 1],
            ['nama_mapel' => 'Pendidikan Agama Islam', 'kelas' => 1, 'guru' => 1],
        ];

        $subjects = [];

        foreach ($subjectMap as $def) {
            $subjects[$def['nama_mapel']] = MataPelajaran::create([
                'nama_mapel' => $def['nama_mapel'],
                'guru_id' => $teachers[$def['guru']]->id,
                'kelas_id' => $classes[$def['kelas']]->id,
            ]);
        }

        return $subjects;
    }

    /**
     * @param  Kelas[]  $classes
     * @return array<int, array<string, Siswa[]>>
     */
    private function seedStudents(array $classes, TahunAjaran $tahunAjaran): array
    {
        $students = [];
        $nisCounter = 10001;

        $distribution = [
            0 => 20, // 7-A
            1 => 15, // 8-A
        ];

        foreach ($distribution as $classIdx => $count) {
            $kelasId = $classes[$classIdx]->id;
            $students[$classIdx] = [];

            for ($i = 0; $i < $count; $i++) {
                $user = User::create([
                    'name' => fake('id_ID')->name(),
                    'email' => fake('id_ID')->unique()->safeEmail(),
                    'password' => Hash::make('password'),
                    'jenis_kelamin' => fake()->randomElement(['L', 'P']),
                    'email_verified_at' => now(),
                ]);
                $user->assignRole('student');

                $siswa = Siswa::create([
                    'nis' => (string) $nisCounter++,
                    'user_id' => $user->id,
                    'kelas_id' => $kelasId,
                ]);

                SiswaKelasHistory::create([
                    'siswa_id' => $siswa->id,
                    'kelas_id' => $kelasId,
                    'tahun_ajaran_id' => $tahunAjaran->id,
                ]);

                $students[$classIdx][] = $siswa;
            }
        }

        return $students;
    }

    /**
     * @param  array<string, MataPelajaran>  $subjects
     * @param  Kelas[]  $classes
     * @param  User[]  $teachers
     * @return array<int, AktivitasPembelajaran[]>
     */
    private function seedActivities(array $subjects, array $classes, array $teachers, TahunAjaran $tahunAjaran): array
    {
        $topicsBySubject = [
            'Matematika' => ['Persamaan Linear', 'Sistem Persamaan', 'Fungsi Kuadrat', 'Trigonometri', 'Statistika', 'Peluang', 'Logaritma', 'Matriks', 'Barisan & Deret', 'Lingkaran'],
            'Bahasa Indonesia' => ['Teks Deskripsi', 'Teks Narasi', 'Puisi Rakyat', 'Teks Eksposisi', 'Surat Resmi', 'Debat', 'Cerpen', 'Drama', 'Teks Prosedur', 'Resensi'],
            'IPA' => ['Sistem Pencernaan', 'Rangka & Otot', 'Ekosistem', 'Energi Listrik', 'Tata Surya', 'Zat & Wujudnya', 'Fotosintesis', 'Pencemaran', 'Gerak Benda', 'Kalor'],
            'Bahasa Inggris' => ['Simple Present', 'Descriptive Text', 'Recount Text', 'Narrative Text', 'Passive Voice', 'Conditional Sentences', 'Report Text', 'Offering Help', 'Giving Opinion', 'Job Interview'],
            'IPS' => ['Letak Geografis', 'Sumber Daya Alam', 'Perdagangan', 'Kolonialisme', 'ASEAN', 'Globalisasi', 'Interaksi Sosial', 'Lembaga Sosial', 'Pasar & Harga', 'Koperasi'],
            'Pendidikan Agama Islam' => ['Thaharah', 'Shalat Wajib', 'Puasa Ramadhan', 'Zakat', 'Haji & Umrah', 'Aqidah Islam', 'Akhlak Terpuji', 'Sejarah Nabi', 'Al-Quran Hadits', 'Muamalah'],
        ];

        $classMapping = [
            'Matematika' => 0,
            'Bahasa Indonesia' => 0,
            'IPA' => 0,
            'Bahasa Inggris' => 1,
            'IPS' => 1,
            'Pendidikan Agama Islam' => 1,
        ];

        $activities = [];

        $semesterStart = $tahunAjaran->tanggal_mulai->copy();
        $semesterEnd = $tahunAjaran->tanggal_selesai->copy();
        $daysInRange = max(1, (int) $semesterStart->diffInDays($semesterEnd));

        foreach ($subjects as $name => $subject) {
            $classIdx = $classMapping[$name];
            $activities[$classIdx] ??= [];
            $topics = $topicsBySubject[$name];

            for ($i = 0; $i < 10; $i++) {
                $activities[$classIdx][] = AktivitasPembelajaran::create([
                    'tanggal' => $semesterStart->copy()->addDays(random_int(0, $daysInRange))->format('Y-m-d'),
                    'topik' => $topics[$i],
                    'catatan' => fake()->optional(0.7)->paragraph(),
                    'kelas_id' => $classes[$classIdx]->id,
                    'mata_pelajaran_id' => $subject->id,
                    'guru_id' => $teachers[$classIdx]->id,
                ]);
            }
        }

        return $activities;
    }

    /**
     * @param  array<int, AktivitasPembelajaran[]>  $activities
     * @param  array<int, Siswa[]>  $students
     */
    private function seedDetailActivities(array $activities, array $students): void
    {
        $detailData = [];

        foreach ($activities as $classIdx => $classActivities) {
            foreach ($classActivities as $activity) {
                foreach ($students[$classIdx] as $siswa) {
                    $kehadiran = fake()->randomElement([
                        'hadir', 'hadir', 'hadir', 'hadir', 'hadir', 'hadir',
                        'izin', 'izin',
                        'sakit',
                        'alpa',
                    ]);

                    $detailData[] = [
                        'kehadiran' => $kehadiran,
                        'partisipasi' => $kehadiran === 'hadir'
                            ? fake()->randomElement([3, 3, 3, 4, 4, 2, 2, 2, 1])
                            : null,
                        'nilai' => $kehadiran === 'hadir'
                            ? fake()->numberBetween(50, 100)
                            : null,
                        'catatan' => fake()->optional(0.3)->sentence(),
                        'aktivitas_pembelajaran_id' => $activity->id,
                        'siswa_id' => $siswa->id,
                    ];
                }
            }
        }

        foreach (array_chunk($detailData, 500) as $chunk) {
            DetailAktivitas::insert($chunk);
        }
    }
}
