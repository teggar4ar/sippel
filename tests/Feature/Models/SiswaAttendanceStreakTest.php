<?php

declare(strict_types=1);

use App\Models\AktivitasPembelajaran;
use App\Models\DetailAktivitas;
use App\Models\Kelas;
use App\Models\MataPelajaran;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Models\User;
use Illuminate\Support\Facades\DB;

function createStreakDetail(
    Siswa $siswa,
    Kelas $kelas,
    MataPelajaran $mataPelajaran,
    User $teacher,
    string $date,
    string $status,
): void {
    $aktivitas = AktivitasPembelajaran::create([
        'tanggal' => $date,
        'topik' => 'Materi '.$date,
        'kelas_id' => $kelas->id,
        'mata_pelajaran_id' => $mataPelajaran->id,
        'guru_id' => $teacher->id,
    ]);

    DetailAktivitas::create([
        'kehadiran' => $status,
        'aktivitas_pembelajaran_id' => $aktivitas->id,
        'siswa_id' => $siswa->id,
    ]);
}

function createStreakSubject(Kelas $kelas, User $teacher): MataPelajaran
{
    return MataPelajaran::create([
        'nama_mapel' => 'Matematika '.$kelas->id,
        'guru_id' => $teacher->id,
        'kelas_id' => $kelas->id,
    ]);
}

it('counts consecutive attendance from the most recent activity and stops at the first absence', function () {
    $teacher = User::factory()->create();
    $studentUser = User::factory()->create();
    $tahunAjaran = TahunAjaran::factory()->create();
    $kelas = Kelas::factory()->create([
        'tahun_ajaran_id' => $tahunAjaran->id,
        'wali_kelas_id' => $teacher->id,
    ]);
    $siswa = Siswa::factory()->create([
        'user_id' => $studentUser->id,
        'kelas_id' => $kelas->id,
    ]);
    $mataPelajaran = createStreakSubject($kelas, $teacher);

    createStreakDetail($siswa, $kelas, $mataPelajaran, $teacher, '2026-07-02', 'hadir');
    createStreakDetail($siswa, $kelas, $mataPelajaran, $teacher, '2026-07-03', 'izin');
    createStreakDetail($siswa, $kelas, $mataPelajaran, $teacher, '2026-07-04', 'hadir');
    createStreakDetail($siswa, $kelas, $mataPelajaran, $teacher, '2026-07-05', 'hadir');

    DB::flushQueryLog();
    DB::enableQueryLog();

    expect($siswa->getAttendanceStreak())->toBe(2);

    $streakQueries = collect(DB::getQueryLog())
        ->pluck('query')
        ->map(fn (string $query): string => str_replace(['`', '"'], '', mb_strtolower($query)))
        ->filter(fn (string $query): bool => str_contains($query, 'from detail_aktivitas'));

    expect($streakQueries)->toHaveCount(1)
        ->and($streakQueries->first())->toContain('limit 100');
});

it('limits the attendance streak to the selected academic year', function () {
    $teacher = User::factory()->create();
    $studentUser = User::factory()->create();
    $selectedYear = TahunAjaran::factory()->create([
        'nama_tahun' => '2025/2026',
        'semester' => 'Genap',
    ]);
    $otherYear = TahunAjaran::factory()->create([
        'nama_tahun' => '2026/2027',
        'semester' => 'Ganjil',
    ]);
    $selectedKelas = Kelas::factory()->create([
        'tahun_ajaran_id' => $selectedYear->id,
        'wali_kelas_id' => $teacher->id,
        'grup_kelas' => 'A',
    ]);
    $otherKelas = Kelas::factory()->create([
        'tahun_ajaran_id' => $otherYear->id,
        'wali_kelas_id' => $teacher->id,
        'grup_kelas' => 'B',
    ]);
    $siswa = Siswa::factory()->create([
        'user_id' => $studentUser->id,
        'kelas_id' => $otherKelas->id,
    ]);
    $selectedSubject = createStreakSubject($selectedKelas, $teacher);
    $otherSubject = createStreakSubject($otherKelas, $teacher);

    createStreakDetail($siswa, $selectedKelas, $selectedSubject, $teacher, '2026-06-29', 'hadir');
    createStreakDetail($siswa, $selectedKelas, $selectedSubject, $teacher, '2026-06-30', 'hadir');
    createStreakDetail($siswa, $otherKelas, $otherSubject, $teacher, '2026-07-01', 'alpa');

    expect($siswa->getAttendanceStreak())->toBe(0)
        ->and($siswa->getAttendanceStreak($selectedYear->id))->toBe(2);
});
