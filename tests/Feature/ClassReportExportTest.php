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

it('shows correct mata pelajaran and guru using injected model', function () {
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
        'nilai' => 80,
        'partisipasi' => 4,
    ]);

    $this->mataPelajaran->load('guru');
    $export = new ClassReportExport($this->kelas, null, null, $this->mataPelajaran);
    $collection = $export->collection();

    // First data row (index 2)
    $dataRow = $collection->get(2);
    expect($dataRow[4])->toBe('Matematika');         // Mata Pelajaran
    expect($dataRow[5])->toBe($this->teacher->name); // Guru Pengampu
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
        'nilai' => 90,
        'partisipasi' => 5,
    ]);

    DetailAktivitas::create([
        'siswa_id' => $siswa->id,
        'aktivitas_pembelajaran_id' => $aktivitasBahasa->id,
        'kehadiran' => 'izin',
        'nilai' => 75,
        'partisipasi' => 3,
    ]);

    $this->mataPelajaran->load('guru');
    $export = new ClassReportExport($this->kelas, null, null, $this->mataPelajaran);
    $collection = $export->collection();

    // Should only have 1 date column (one date for this subject)
    $header1 = $collection->first();
    // Fixed 7 columns + 3 sub-columns for 1 date = 10 total
    expect(count(array_filter($header1, fn ($v) => $v !== '')))->toBe(8); // 7 fixed + 1 date header

    // Subject column shows Matematika, not Bahasa Indonesia
    $dataRow = $collection->get(2);
    expect($dataRow[4])->toBe('Matematika');
    expect($dataRow[5])->toBe($this->teacher->name);

    // kehadiran for this student on this date should be Hadir (from Matematika activity)
    expect($dataRow[7])->toBe('Hadir');
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
        'nilai' => 50,
        'partisipasi' => 1,
    ]);

    DetailAktivitas::create([
        'siswa_id' => $siswa->id,
        'aktivitas_pembelajaran_id' => $newAktivitas->id,
        'kehadiran' => 'hadir',
        'nilai' => 85,
        'partisipasi' => 5,
    ]);

    $this->mataPelajaran->load('guru');
    $export = new ClassReportExport($this->kelas, null, null, $this->mataPelajaran);
    $collection = $export->collection();

    // Should only have 1 date (the new semester date) — old semester is filtered out
    $header1 = $collection->first();
    $dates = array_values(array_filter(array_slice($header1, 7), fn ($v) => $v !== ''));
    expect($dates)->toHaveCount(1);
    expect($dates[0])->toBe('01/02/2026');

    // Data row should show kehadiran = Hadir (from new semester), not Alpa
    $dataRow = $collection->get(2);
    expect($dataRow[7])->toBe('Hadir');
});
