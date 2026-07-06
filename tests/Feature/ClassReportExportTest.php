<?php

/**
 * @property \App\Models\TahunAjaran $tahunAjaran
 * @property \App\Models\User        $teacher
 * @property \App\Models\Kelas       $kelas
 * @property \App\Models\MataPelajaran $mataPelajaran
 */
declare(strict_types=1);

use App\Enums\Keaktifan;
use App\Exports\ClassReportExport;
use App\Models\AktivitasPembelajaran;
use App\Models\DetailAktivitas;
use App\Models\Kelas;
use App\Models\MataPelajaran;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Models\User;
use Carbon\Carbon;

beforeEach(function () {
    $this->tahunAjaran = TahunAjaran::factory()->create(['status' => true]);

    $this->teacher = User::factory()->create();
    $this->teacher->assignRole('teacher');

    $this->kelas = Kelas::factory()->create([
        'tahun_ajaran_id' => $this->tahunAjaran->id,
        'wali_kelas_id' => $this->teacher->id,
    ]);

    $this->mataPelajaran = MataPelajaran::create([
        'kelas_id' => $this->kelas->id,
        'guru_id' => $this->teacher->id,
        'nama_mapel' => 'Matematika',
    ]);
});

it('generates excel with correct header structure', function () {
    $export = new ClassReportExport($this->kelas);

    $headers = $export->headings();
    expect($headers)->toBe([
        'No',
        'NIS',
        'Nama',
        'Tanggal',
        'Kehadiran',
        'Keaktifan',
        'Catatan Observasi',
    ]);
});

it('orders records by activity date', function () {
    // Create test students with activity records on different dates
    $siswa1 = Siswa::factory()->create(['kelas_id' => $this->kelas->id]);

    $tanggal1 = Carbon::parse('2026-02-07');
    $tanggal2 = Carbon::parse('2026-02-08');

    $aktivitas1 = AktivitasPembelajaran::create([
        'kelas_id' => $this->kelas->id,
        'mata_pelajaran_id' => $this->mataPelajaran->id,
        'guru_id' => $this->teacher->id,
        'tanggal' => $tanggal1,
        'topik' => 'Test Topic 1',
    ]);

    $aktivitas2 = AktivitasPembelajaran::create([
        'kelas_id' => $this->kelas->id,
        'mata_pelajaran_id' => $this->mataPelajaran->id,
        'guru_id' => $this->teacher->id,
        'tanggal' => $tanggal2,
        'topik' => 'Test Topic 2',
    ]);

    DetailAktivitas::create([
        'siswa_id' => $siswa1->id,
        'aktivitas_pembelajaran_id' => $aktivitas1->id,
        'kehadiran' => 'hadir',
        'keaktifan' => Keaktifan::SangatAktif,
    ]);

    DetailAktivitas::create([
        'siswa_id' => $siswa1->id,
        'aktivitas_pembelajaran_id' => $aktivitas2->id,
        'kehadiran' => 'hadir',
        'keaktifan' => Keaktifan::Aktif,
    ]);

    $export = new ClassReportExport($this->kelas);
    $collection = $export->collection();

    expect($collection->count())->toBe(2);
    expect($collection->first()->aktivitasPembelajaran->tanggal->format('d/m/Y'))
        ->toBe($tanggal1->format('d/m/Y'));
    expect($collection->last()->aktivitasPembelajaran->tanggal->format('d/m/Y'))
        ->toBe($tanggal2->format('d/m/Y'));
});

it('maps detail rows with attendance and keaktifan labels', function () {
    $siswa = Siswa::factory()->create(['kelas_id' => $this->kelas->id]);

    $aktivitas = AktivitasPembelajaran::create([
        'kelas_id' => $this->kelas->id,
        'mata_pelajaran_id' => $this->mataPelajaran->id,
        'guru_id' => $this->teacher->id,
        'tanggal' => Carbon::parse('2026-02-07'),
        'topik' => 'Test Topic',
    ]);

    DetailAktivitas::create([
        'siswa_id' => $siswa->id,
        'aktivitas_pembelajaran_id' => $aktivitas->id,
        'kehadiran' => 'hadir',
        'keaktifan' => Keaktifan::SangatAktif,
    ]);

    $export = new ClassReportExport($this->kelas, $this->mataPelajaran);
    $collection = $export->collection();

    $row = $export->map($collection->first());
    expect($row[3])->toBe('07/02/2026');
    expect($row[4])->toBe('Hadir');
    expect($row[5])->toBe('Sangat Aktif');
});

it('only includes activities for the specified subject when mata pelajaran is passed', function () {
    // Two subjects for the same class
    $otherTeacher = User::factory()->create();
    $otherTeacher->assignRole('teacher');

    $otherMapel = MataPelajaran::create([
        'kelas_id' => $this->kelas->id,
        'guru_id' => $otherTeacher->id,
        'nama_mapel' => 'Bahasa Indonesia',
    ]);

    $siswa = Siswa::factory()->create(['kelas_id' => $this->kelas->id]);
    $tanggal = Carbon::parse('2026-03-01');

    // Activity for Matematika
    $aktivitasMatematika = AktivitasPembelajaran::create([
        'kelas_id' => $this->kelas->id,
        'mata_pelajaran_id' => $this->mataPelajaran->id,
        'guru_id' => $this->teacher->id,
        'tanggal' => $tanggal,
        'topik' => 'Aljabar',
    ]);

    // Activity for Bahasa Indonesia on the same date
    $aktivitasBahasa = AktivitasPembelajaran::create([
        'kelas_id' => $this->kelas->id,
        'mata_pelajaran_id' => $otherMapel->id,
        'guru_id' => $otherTeacher->id,
        'tanggal' => $tanggal,
        'topik' => 'Membaca',
    ]);

    DetailAktivitas::create([
        'siswa_id' => $siswa->id,
        'aktivitas_pembelajaran_id' => $aktivitasMatematika->id,
        'kehadiran' => 'hadir',
        'keaktifan' => Keaktifan::SangatAktif,
    ]);

    DetailAktivitas::create([
        'siswa_id' => $siswa->id,
        'aktivitas_pembelajaran_id' => $aktivitasBahasa->id,
        'kehadiran' => 'izin',
        'keaktifan' => null,
    ]);

    $export = new ClassReportExport($this->kelas, $this->mataPelajaran);
    $collection = $export->collection();

    expect($collection)->toHaveCount(1);
    expect($collection->first()->aktivitasPembelajaran->mata_pelajaran_id)
        ->toBe($this->mataPelajaran->id);
});

it('scopes activities to kelas via aktivitas_pembelajaran when spanning multiple semesters', function () {
    // Simulate GantiSemester: student moved to new kelas, old kelas has old activities
    $oldTahunAjaran = TahunAjaran::factory()->create(['status' => false]);
    $oldKelas = Kelas::factory()->create([
        'tahun_ajaran_id' => $oldTahunAjaran->id,
        'wali_kelas_id' => $this->teacher->id,
    ]);
    $oldMapel = MataPelajaran::create([
        'kelas_id' => $oldKelas->id,
        'guru_id' => $this->teacher->id,
        'nama_mapel' => 'Matematika',
    ]);

    // Student now lives in $this->kelas but had activity in old kelas
    $siswa = Siswa::factory()->create(['kelas_id' => $this->kelas->id]);

    // Old semester activity (wrong kelas)
    $oldAktivitas = AktivitasPembelajaran::create([
        'kelas_id' => $oldKelas->id,
        'mata_pelajaran_id' => $oldMapel->id,
        'guru_id' => $this->teacher->id,
        'tanggal' => Carbon::parse('2025-09-01'),
        'topik' => 'Lama',
    ]);

    // New semester activity (correct kelas)
    $newAktivitas = AktivitasPembelajaran::create([
        'kelas_id' => $this->kelas->id,
        'mata_pelajaran_id' => $this->mataPelajaran->id,
        'guru_id' => $this->teacher->id,
        'tanggal' => Carbon::parse('2026-02-01'),
        'topik' => 'Baru',
    ]);

    DetailAktivitas::create([
        'siswa_id' => $siswa->id,
        'aktivitas_pembelajaran_id' => $oldAktivitas->id,
        'kehadiran' => 'alpa',
        'keaktifan' => null,
    ]);

    DetailAktivitas::create([
        'siswa_id' => $siswa->id,
        'aktivitas_pembelajaran_id' => $newAktivitas->id,
        'kehadiran' => 'hadir',
        'keaktifan' => Keaktifan::SangatAktif,
    ]);

    $export = new ClassReportExport($this->kelas, $this->mataPelajaran);
    $collection = $export->collection();
    expect($collection)->toHaveCount(1);
    expect($collection->first()->aktivitasPembelajaran->kelas_id)->toBe($this->kelas->id);
});
