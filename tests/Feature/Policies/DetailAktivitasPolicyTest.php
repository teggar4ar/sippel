<?php

declare(strict_types=1);

use App\Models\AktivitasPembelajaran;
use App\Models\DetailAktivitas;
use App\Models\Kelas;
use App\Models\MataPelajaran;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Models\User;
use App\Policies\DetailAktivitasPolicy;
use Illuminate\Support\Facades\DB;

function createPolicyDetail(Kelas $kelas, User $teacher): DetailAktivitas
{
    $mataPelajaran = MataPelajaran::create([
        'nama_mapel' => 'Matematika '.$kelas->id,
        'guru_id' => $teacher->id,
        'kelas_id' => $kelas->id,
    ]);
    $aktivitas = AktivitasPembelajaran::create([
        'tanggal' => now()->toDateString(),
        'topik' => 'Materi kebijakan akses',
        'kelas_id' => $kelas->id,
        'mata_pelajaran_id' => $mataPelajaran->id,
        'guru_id' => $teacher->id,
    ]);
    $studentUser = User::factory()->create();
    $siswa = Siswa::factory()->create([
        'user_id' => $studentUser->id,
        'kelas_id' => $kelas->id,
    ]);
    $detail = DetailAktivitas::create([
        'kehadiran' => 'hadir',
        'aktivitas_pembelajaran_id' => $aktivitas->id,
        'siswa_id' => $siswa->id,
    ]);
    $detail->setRelation('aktivitasPembelajaran', $aktivitas);

    return $detail;
}

it('allows teachers to access details from wali and subject classes only', function () {
    $teacher = User::factory()->create();
    $teacher->assignRole('teacher');
    $otherTeacher = User::factory()->create();
    $tahunAjaran = TahunAjaran::factory()->create();

    $waliKelas = Kelas::factory()->create([
        'tahun_ajaran_id' => $tahunAjaran->id,
        'wali_kelas_id' => $teacher->id,
        'grup_kelas' => 'A',
    ]);
    $subjectKelas = Kelas::factory()->create([
        'tahun_ajaran_id' => $tahunAjaran->id,
        'wali_kelas_id' => $otherTeacher->id,
        'grup_kelas' => 'B',
    ]);
    $otherKelas = Kelas::factory()->create([
        'tahun_ajaran_id' => $tahunAjaran->id,
        'wali_kelas_id' => $otherTeacher->id,
        'grup_kelas' => 'C',
    ]);

    $waliDetail = createPolicyDetail($waliKelas, $otherTeacher);
    $subjectDetail = createPolicyDetail($subjectKelas, $teacher);
    $otherDetail = createPolicyDetail($otherKelas, $otherTeacher);
    $policy = new DetailAktivitasPolicy();

    expect($policy->view($teacher, $waliDetail))->toBeTrue()
        ->and($policy->update($teacher, $subjectDetail))->toBeTrue()
        ->and($policy->delete($teacher, $otherDetail))->toBeFalse();
});

it('queries teacher class assignments only once per request', function () {
    $teacher = User::factory()->create();
    $teacher->assignRole('teacher');
    $teacher->hasRole('teacher');
    $tahunAjaran = TahunAjaran::factory()->create();
    $kelas = Kelas::factory()->create([
        'tahun_ajaran_id' => $tahunAjaran->id,
        'wali_kelas_id' => $teacher->id,
    ]);
    $detail = createPolicyDetail($kelas, $teacher);
    $policy = new DetailAktivitasPolicy();

    DB::flushQueryLog();
    DB::enableQueryLog();

    expect($policy->view($teacher, $detail))->toBeTrue()
        ->and($policy->update($teacher, $detail))->toBeTrue()
        ->and($policy->delete($teacher, $detail))->toBeTrue();

    $assignmentQueries = collect(DB::getQueryLog())
        ->pluck('query')
        ->map(fn (string $query): string => str_replace(['`', '"'], '', mb_strtolower($query)))
        ->filter(fn (string $query): bool => str_contains($query, 'from kelas')
            || str_contains($query, 'from mata_pelajaran'));

    expect($assignmentQueries)->toHaveCount(2);
});
