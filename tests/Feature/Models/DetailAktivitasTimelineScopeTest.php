<?php

declare(strict_types=1);

use App\Models\AktivitasPembelajaran;
use App\Models\DetailAktivitas;
use App\Models\Kelas;
use App\Models\MataPelajaran;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Models\User;

function createTimelineDetail(
    Siswa $siswa,
    Kelas $kelas,
    MataPelajaran $mataPelajaran,
    User $teacher,
    string $date,
): DetailAktivitas {
    $aktivitas = AktivitasPembelajaran::create([
        'tanggal' => $date,
        'topik' => 'Materi '.$date,
        'kelas_id' => $kelas->id,
        'mata_pelajaran_id' => $mataPelajaran->id,
        'guru_id' => $teacher->id,
    ]);

    return DetailAktivitas::create([
        'kehadiran' => 'hadir',
        'aktivitas_pembelajaran_id' => $aktivitas->id,
        'siswa_id' => $siswa->id,
    ]);
}

function createTimelineSubject(Kelas $kelas, User $teacher, string $name): MataPelajaran
{
    return MataPelajaran::create([
        'nama_mapel' => $name,
        'guru_id' => $teacher->id,
        'kelas_id' => $kelas->id,
    ]);
}

it('filters and orders timeline details using joins without exists subqueries', function () {
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
    $sameYearKelas = Kelas::factory()->create([
        'tahun_ajaran_id' => $selectedYear->id,
        'wali_kelas_id' => $teacher->id,
        'grup_kelas' => 'B',
    ]);
    $otherYearKelas = Kelas::factory()->create([
        'tahun_ajaran_id' => $otherYear->id,
        'wali_kelas_id' => $teacher->id,
        'grup_kelas' => 'C',
    ]);
    $siswa = Siswa::factory()->create([
        'user_id' => $studentUser->id,
        'kelas_id' => $selectedKelas->id,
    ]);
    $selectedSubject = createTimelineSubject($selectedKelas, $teacher, 'Matematika');
    $sameYearSubject = createTimelineSubject($sameYearKelas, $teacher, 'IPA');
    $otherYearSubject = createTimelineSubject($otherYearKelas, $teacher, 'Bahasa');

    $oldest = createTimelineDetail($siswa, $selectedKelas, $selectedSubject, $teacher, '2026-07-01');
    $sameDateFirst = createTimelineDetail($siswa, $selectedKelas, $selectedSubject, $teacher, '2026-07-03');
    $sameDateLast = createTimelineDetail($siswa, $selectedKelas, $selectedSubject, $teacher, '2026-07-03');
    $otherClass = createTimelineDetail($siswa, $sameYearKelas, $sameYearSubject, $teacher, '2026-07-04');
    createTimelineDetail($siswa, $otherYearKelas, $otherYearSubject, $teacher, '2026-07-05');

    $query = DetailAktivitas::where('siswa_id', $siswa->id)
        ->withTimelineJoin($selectedKelas->id, $selectedYear->id);
    $sql = str_replace(['`', '"'], '', mb_strtolower($query->toSql()));

    expect($sql)->toContain('join aktivitas_pembelajaran')
        ->and($sql)->toContain('join kelas')
        ->and($sql)->not->toContain('exists (');

    expect($query->get()->modelKeys())->toBe([
        $sameDateLast->id,
        $sameDateFirst->id,
        $oldest->id,
    ]);

    $allSelectedYear = DetailAktivitas::where('siswa_id', $siswa->id)
        ->withTimelineJoin(null, $selectedYear->id)
        ->get();

    expect($allSelectedYear->modelKeys())->toBe([
        $otherClass->id,
        $sameDateLast->id,
        $sameDateFirst->id,
        $oldest->id,
    ]);
});

it('excludes soft-deleted activities and eager loads each activity subject', function () {
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
    $mataPelajaran = createTimelineSubject($kelas, $teacher, 'Matematika');
    $visible = createTimelineDetail($siswa, $kelas, $mataPelajaran, $teacher, '2026-07-01');
    $deleted = createTimelineDetail($siswa, $kelas, $mataPelajaran, $teacher, '2026-07-02');
    $deleted->aktivitasPembelajaran()->firstOrFail()->delete();

    $timeline = DetailAktivitas::where('siswa_id', $siswa->id)
        ->withTimelineJoin($kelas->id, $tahunAjaran->id)
        ->get();

    expect($timeline->modelKeys())->toBe([$visible->id])
        ->and($timeline->first()->relationLoaded('aktivitasPembelajaran'))->toBeTrue()
        ->and($timeline->first()->aktivitasPembelajaran->relationLoaded('mataPelajaran'))->toBeTrue()
        ->and($timeline->first()->aktivitasPembelajaran->mataPelajaran->is($mataPelajaran))->toBeTrue();
});
