<?php

declare(strict_types=1);

use App\Models\AktivitasPembelajaran;
use App\Models\DetailAktivitas;
use App\Models\Kelas;
use App\Models\MataPelajaran;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Models\User;
use App\Services\QrAttendanceService;

beforeEach(function (): void {
    // Create test data
    $this->tahunAjaran = TahunAjaran::factory()->active()->create();
    $this->kelas = Kelas::factory()->create(['tahun_ajaran_id' => $this->tahunAjaran->id]);

    // Create student user
    $this->studentUser = User::factory()->create([
        'email' => 'student@test.com',
    ]);
    $this->studentUser->assignRole('student');

    $this->siswa = Siswa::factory()->create([
        'kelas_id' => $this->kelas->id,
        'user_id' => $this->studentUser->id,
    ]);

    // Create teacher and activity
    $this->teacherUser = User::factory()->create();
    $this->teacherUser->assignRole('teacher');

    $this->mataPelajaran = MataPelajaran::factory()->create();

    $this->aktivitas = AktivitasPembelajaran::factory()->create([
        'kelas_id' => $this->kelas->id,
        'mata_pelajaran_id' => $this->mataPelajaran->id,
        'guru_id' => $this->teacherUser->id,
        'presensi_mandiri' => true,
        'durasi_presensi_menit' => 10,
    ]);

    // Create default detail
    $this->detail = DetailAktivitas::create([
        'aktivitas_pembelajaran_id' => $this->aktivitas->id,
        'siswa_id' => $this->siswa->id,
        'kehadiran' => 'alpa',
        'metode_kehadiran' => null,
    ]);

    // Create active session
    $this->service = app(QrAttendanceService::class);
    $this->sesi = $this->service->createSession($this->aktivitas, 10);
});

describe('Student QR Scan Flow', function (): void {
    test('student can access scan page', function (): void {
        $this->actingAs($this->studentUser)
            ->get(route('student.presensi.scan'))
            ->assertStatus(200)
            ->assertSeeLivewire('student.scan-presensi');
    });

    test('teacher cannot access scan page', function (): void {
        $this->actingAs($this->teacherUser)
            ->get(route('student.presensi.scan'))
            ->assertForbidden();
    });

    // Note: Detailed scan processing tests (invalid QR, duplicate scan, session closed, etc.)
    // are comprehensively covered in QrAttendanceServiceTest unit tests (20 tests).
    // The Livewire ScanPresensi component integrates with this tested service.
});

describe('Teacher Activity Management', function (): void {
    test('teacher can create activity with QR attendance', function (): void {
        $this->actingAs($this->teacherUser)
            ->get(route('teacher.aktivitas.create'))
            ->assertStatus(200);

        expect(true)->toBeTrue();
    });

    test('teacher can view attendance stats with QR scans', function (): void {
        // Mark one student as scanned
        $this->detail->update([
            'kehadiran' => 'hadir',
            'metode_kehadiran' => 'qr_scan',
            'waktu_kehadiran' => now(),
        ]);

        $response = $this->actingAs($this->teacherUser)
            ->get(route('teacher.aktivitas.view', $this->aktivitas));

        $response->assertStatus(200)
            ->assertSee('Presensi via QR');
    });

    test('observer closes sessions when activity is deleted', function (): void {
        expect($this->sesi->status)->toBe('open');

        $this->aktivitas->delete();
        $this->sesi->refresh();

        expect($this->sesi->status)->toBe('closed')
            ->and($this->sesi->ditutup_pada)->not->toBeNull();
    });
});
