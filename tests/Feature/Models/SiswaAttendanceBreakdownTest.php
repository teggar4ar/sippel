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

function createBreakdownDetail(
    Siswa $siswa,
    Kelas $kelas,
    MataPelajaran $mataPelajaran,
    User $teacher,
    string $status,
    string $date,
): void {
    $aktivitas = AktivitasPembelajaran::create([
        'tanggal' => $date,
        'topik' => 'Materi '.$status,
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

function createBreakdownContext(): array
{
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
    $matematika = MataPelajaran::create([
        'nama_mapel' => 'Matematika',
        'guru_id' => $teacher->id,
        'kelas_id' => $kelas->id,
    ]);
    $bahasa = MataPelajaran::create([
        'nama_mapel' => 'Bahasa Indonesia',
        'guru_id' => $teacher->id,
        'kelas_id' => $kelas->id,
    ]);

    return compact('teacher', 'kelas', 'siswa', 'matematika', 'bahasa');
}

it('uses eager-loaded activity details without additional queries', function () {
    $context = createBreakdownContext();

    createBreakdownDetail($context['siswa'], $context['kelas'], $context['matematika'], $context['teacher'], 'hadir', '2026-07-01');
    createBreakdownDetail($context['siswa'], $context['kelas'], $context['matematika'], $context['teacher'], 'izin', '2026-07-02');
    createBreakdownDetail($context['siswa'], $context['kelas'], $context['bahasa'], $context['teacher'], 'sakit', '2026-07-03');
    createBreakdownDetail($context['siswa'], $context['kelas'], $context['bahasa'], $context['teacher'], 'alpa', '2026-07-04');

    $context['siswa']->load('detailAktivitas');

    DB::flushQueryLog();
    DB::enableQueryLog();

    expect($context['siswa']->getAttendanceBreakdown())->toBe([
        'total' => 4,
        'hadir' => 1,
        'izin' => 1,
        'sakit' => 1,
        'alpa' => 1,
    ])->and(DB::getQueryLog())->toBeEmpty();
});

it('queries the database when filtering by subject even if details are loaded', function () {
    $context = createBreakdownContext();

    createBreakdownDetail($context['siswa'], $context['kelas'], $context['matematika'], $context['teacher'], 'hadir', '2026-07-01');
    createBreakdownDetail($context['siswa'], $context['kelas'], $context['matematika'], $context['teacher'], 'izin', '2026-07-02');
    createBreakdownDetail($context['siswa'], $context['kelas'], $context['bahasa'], $context['teacher'], 'alpa', '2026-07-03');

    $context['siswa']->load('detailAktivitas');

    DB::flushQueryLog();
    DB::enableQueryLog();

    expect($context['siswa']->getAttendanceBreakdown($context['matematika']->id))->toBe([
        'total' => 2,
        'hadir' => 1,
        'izin' => 1,
        'sakit' => 0,
        'alpa' => 0,
    ]);

    $detailQueries = collect(DB::getQueryLog())
        ->pluck('query')
        ->map(fn (string $query): string => str_replace(['`', '"'], '', mb_strtolower($query)))
        ->filter(fn (string $query): bool => str_contains($query, 'from detail_aktivitas'));

    expect($detailQueries)->toHaveCount(1);
});
