<?php

declare(strict_types=1);

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
    $collection = $export->collection();

    // Should have at least 2 header rows
    expect($collection->count())->toBeGreaterThan(1);

    // Check header row 1 - Fixed columns
    $header1 = $collection->first();
    expect($header1[0])->toBe('No');
    expect($header1[1])->toBe('NIS');
    expect($header1[2])->toBe('Nama');
    expect($header1[3])->toBe('Kelas');
    expect($header1[4])->toBe('Mata Pelajaran');
    expect($header1[5])->toBe('Guru Pengampu');
    expect($header1[6])->toBe('Wali Kelas');
});

it('generates excel with date column groups', function () {
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
        'nilai' => 85,
        'partisipasi' => 4,
    ]);

    DetailAktivitas::create([
        'siswa_id' => $siswa1->id,
        'aktivitas_pembelajaran_id' => $aktivitas2->id,
        'kehadiran' => 'hadir',
        'nilai' => 90,
        'partisipasi' => 5,
    ]);

    // Export with date range
    $export = new ClassReportExport($this->kelas, $tanggal1, $tanggal2);
    $collection = $export->collection();

    // Should have 2 header rows + student rows
    expect($collection->count())->toBeGreaterThan(2);

    // Check header row 1 contains dates
    $header1 = $collection->first();
    expect($header1[7])->toBe($tanggal1->format('d/m/Y'));

    // Check header row 2 contains subcolumns
    $header2 = $collection->get(1);
    expect($header2[7])->toBe('Status kehadiran');
    expect($header2[8])->toBe('Nilai');
    expect($header2[9])->toBe('Partisipasi');
});

it('generates one row per student with pivoted data', function () {
    // Create 2 students
    $siswa1 = Siswa::factory()->create(['kelas_id' => $this->kelas->id]);
    $siswa2 = Siswa::factory()->create(['kelas_id' => $this->kelas->id]);

    $tanggal1 = Carbon::parse('2026-02-07');

    $aktivitas1 = AktivitasPembelajaran::create([
        'kelas_id' => $this->kelas->id,
        'mata_pelajaran_id' => $this->mataPelajaran->id,
        'guru_id' => $this->teacher->id,
        'tanggal' => $tanggal1,
        'topik' => 'Test Topic',
    ]);

    // Only siswa1 has attendance record
    DetailAktivitas::create([
        'siswa_id' => $siswa1->id,
        'aktivitas_pembelajaran_id' => $aktivitas1->id,
        'kehadiran' => 'hadir',
        'nilai' => 85,
        'partisipasi' => 4,
    ]);

    $export = new ClassReportExport($this->kelas, $tanggal1, $tanggal1);
    $collection = $export->collection();

    //    // 2 header rows + 2 student rows = 4 total
    expect($collection->count())->toBe(4);

    // Check student 1 row has data
    $student1Row = $collection->get(2); // First data row after 2 headers
    expect($student1Row[0])->toBe(1); // Row number
    expect($student1Row[7])->toBe('Hadir'); // Attendance status
    expect((int) $student1Row[8])->toBe(85); // Grade (cast to int due to decimal formatting)
    expect((int) $student1Row[9])->toBe(4); // Participation

    // Check student 2 row exists but has empty attendance
    $student2Row = $collection->get(3);
    expect($student2Row[0])->toBe(2); // Row number
    expect($student2Row[7])->toBe(''); // No attendance
    expect($student2Row[8])->toBe(''); // No grade
    expect($student2Row[9])->toBe(''); // No participation
});
