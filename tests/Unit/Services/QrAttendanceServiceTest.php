<?php

declare(strict_types=1);

use App\Models\AktivitasPembelajaran;
use App\Models\DetailAktivitas;
use App\Models\Kelas;
use App\Models\MataPelajaran;
use App\Models\SesiPresensi;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Models\User;
use App\Services\QrAttendanceService;

beforeEach(function (): void {
    $this->service = app(QrAttendanceService::class);

    // Create test data
    $this->tahunAjaran = TahunAjaran::factory()->active()->create();
    $this->kelas = Kelas::factory()->create(['tahun_ajaran_id' => $this->tahunAjaran->id]);
    $this->siswa = Siswa::factory()->create([
        'kelas_id' => $this->kelas->id,
        'qr_secret' => null,
    ]);
    $this->mataPelajaran = MataPelajaran::factory()->create();
    $this->guru = User::factory()->create();
    $this->guru->assignRole('teacher');
});

describe('QR Generation', function (): void {
    test('can generate QR secret for student without one', function (): void {
        expect($this->siswa->qr_secret)->toBeNull();

        $qrData = $this->service->generateQrData($this->siswa);

        $this->siswa->refresh();
        expect($this->siswa->qr_secret)->not->toBeNull()
            ->and($this->siswa->qr_generated_at)->not->toBeNull()
            ->and($qrData)->toContain(':')
            ->and(explode(':', $qrData)[0])->toBe((string) $this->siswa->id);
    });

    test('generates consistent QR data for same student', function (): void {
        $qrData1 = $this->service->generateQrData($this->siswa);
        $qrData2 = $this->service->generateQrData($this->siswa);

        expect($qrData1)->toBe($qrData2);
    });

    test('generates valid HMAC signature', function (): void {
        $this->siswa->generateQrSecret();

        $signature = $this->service->generateHmacSignature(
            $this->siswa->id,
            $this->siswa->qr_secret
        );

        expect($signature)->toHaveLength(64) // SHA256 = 64 hex chars
            ->and($signature)->toMatch('/^[a-f0-9]{64}$/');
    });

    test('generates QR image as base64 data URI', function (): void {
        $qrImage = $this->service->generateQrImage($this->siswa);

        expect($qrImage)->toStartWith('data:image/png;base64,');
    });
});

describe('QR Validation', function (): void {
    test('validates correct QR data', function (): void {
        $qrData = $this->service->generateQrData($this->siswa);

        $validation = $this->service->validateQrData($qrData);

        expect($validation['valid'])->toBeTrue()
            ->and($validation['siswa_id'])->toBe($this->siswa->id)
            ->and($validation['error'])->toBeNull();
    });

    test('rejects QR data with invalid format', function (): void {
        $validation = $this->service->validateQrData('invalid-format');

        expect($validation['valid'])->toBeFalse()
            ->and($validation['error'])->toBe('Format QR code tidak valid');
    });

    test('rejects QR data with invalid signature', function (): void {
        $this->siswa->generateQrSecret();
        $qrData = "{$this->siswa->id}:invalid_signature_here";

        $validation = $this->service->validateQrData($qrData);

        expect($validation['valid'])->toBeFalse()
            ->and($validation['error'])->toBe('Signature QR code tidak valid');
    });

    test('rejects QR data for non-existent student', function (): void {
        $fakeSignature = hash_hmac('sha256', '99999', 'fake-secret');
        $qrData = "99999:{$fakeSignature}";

        $validation = $this->service->validateQrData($qrData);

        expect($validation['valid'])->toBeFalse()
            ->and($validation['error'])->toBe('Siswa tidak ditemukan');
    });
});

describe('Session Management', function (): void {
    test('can create attendance session', function (): void {
        $aktivitas = AktivitasPembelajaran::factory()->create([
            'kelas_id' => $this->kelas->id,
            'mata_pelajaran_id' => $this->mataPelajaran->id,
            'guru_id' => $this->guru->id,
            'presensi_mandiri' => true,
        ]);

        $sesi = $this->service->createSession($aktivitas, 10);

        expect($sesi)->toBeInstanceOf(SesiPresensi::class)
            ->and($sesi->aktivitas_pembelajaran_id)->toBe($aktivitas->id)
            ->and($sesi->status)->toBe('open')
            ->and($sesi->durasi_menit)->toBe(10)
            ->and($sesi->dibuka_pada)->not->toBeNull();
    });

    test('can close attendance session', function (): void {
        $aktivitas = AktivitasPembelajaran::factory()->create([
            'kelas_id' => $this->kelas->id,
            'mata_pelajaran_id' => $this->mataPelajaran->id,
            'guru_id' => $this->guru->id,
            'presensi_mandiri' => true,
        ]);

        $sesi = $this->service->createSession($aktivitas, 10);
        $result = $this->service->closeSession($sesi);

        $sesi->refresh();
        expect($result)->toBeTrue()
            ->and($sesi->status)->toBe('closed')
            ->and($sesi->ditutup_pada)->not->toBeNull();
    });

    test('checks if session is active correctly', function (): void {
        $aktivitas = AktivitasPembelajaran::factory()->create([
            'kelas_id' => $this->kelas->id,
            'mata_pelajaran_id' => $this->mataPelajaran->id,
            'guru_id' => $this->guru->id,
            'presensi_mandiri' => true,
        ]);

        $sesi = $this->service->createSession($aktivitas, 10);

        expect($this->service->isSessionActive($sesi))->toBeTrue();

        $this->service->closeSession($sesi);
        $sesi->refresh();

        expect($this->service->isSessionActive($sesi))->toBeFalse();
    });
});

describe('Scan Processing', function (): void {
    beforeEach(function (): void {
        $this->aktivitas = AktivitasPembelajaran::factory()->create([
            'kelas_id' => $this->kelas->id,
            'mata_pelajaran_id' => $this->mataPelajaran->id,
            'guru_id' => $this->guru->id,
            'presensi_mandiri' => true,
        ]);

        // Create detail with default alpa status
        $this->detail = DetailAktivitas::create([
            'aktivitas_pembelajaran_id' => $this->aktivitas->id,
            'siswa_id' => $this->siswa->id,
            'kehadiran' => 'alpa',
            'metode_kehadiran' => null,
            'nilai' => null,
            'partisipasi' => null,
            'catatan' => null,
        ]);

        $this->sesi = $this->service->createSession($this->aktivitas, 10);
    });

    test('successfully processes valid QR scan', function (): void {
        $qrData = $this->service->generateQrData($this->siswa);

        $result = $this->service->processScan(
            $qrData,
            $this->siswa,
            '127.0.0.1',
            'Test Browser'
        );

        expect($result['success'])->toBeTrue()
            ->and($result['message'])->toContain('berhasil')
            ->and($result['detail_aktivitas_id'])->not->toBeNull();

        $this->detail->refresh();
        expect($this->detail->kehadiran)->toBe('hadir')
            ->and($this->detail->metode_kehadiran)->toBe('qr_scan')
            ->and($this->detail->waktu_kehadiran)->not->toBeNull();
    });

    test('rejects scan with invalid QR signature', function (): void {
        $invalidQr = "{$this->siswa->id}:invalid_signature";

        $result = $this->service->processScan(
            $invalidQr,
            $this->siswa,
            '127.0.0.1',
            'Test Browser'
        );

        expect($result['success'])->toBeFalse()
            ->and($result['message'])->toContain('QR code');
    });

    test('rejects scan when QR belongs to different student', function (): void {
        $otherSiswa = Siswa::factory()->create(['kelas_id' => $this->kelas->id]);
        $otherQrData = $this->service->generateQrData($otherSiswa);

        $result = $this->service->processScan(
            $otherQrData,
            $this->siswa,
            '127.0.0.1',
            'Test Browser'
        );

        expect($result['success'])->toBeFalse()
            ->and($result['message'])->toContain('bukan milik Anda');
    });

    test('rejects scan when no active session exists', function (): void {
        $this->service->closeSession($this->sesi);
        $qrData = $this->service->generateQrData($this->siswa);

        $result = $this->service->processScan(
            $qrData,
            $this->siswa,
            '127.0.0.1',
            'Test Browser'
        );

        expect($result['success'])->toBeFalse()
            ->and($result['message'])->toContain('Tidak ada sesi presensi');
    });

    test('rejects scan when student from different class', function (): void {
        $otherKelas = Kelas::factory()->create(['tahun_ajaran_id' => $this->tahunAjaran->id]);
        $otherSiswa = Siswa::factory()->create(['kelas_id' => $otherKelas->id]);

        // Student from other class tries to scan - will fail because no session for their class
        $qrData = $this->service->generateQrData($otherSiswa);

        $result = $this->service->processScan(
            $qrData,
            $otherSiswa,
            '127.0.0.1',
            'Test Browser'
        );

        expect($result['success'])->toBeFalse()
            ->and($result['message'])->toContain('Tidak ada sesi presensi');
    });

    test('rejects duplicate scan', function (): void {
        $qrData = $this->service->generateQrData($this->siswa);

        // First scan - should succeed
        $result1 = $this->service->processScan(
            $qrData,
            $this->siswa,
            '127.0.0.1',
            'Test Browser'
        );

        expect($result1['success'])->toBeTrue();

        // Second scan - should be rejected
        $result2 = $this->service->processScan(
            $qrData,
            $this->siswa,
            '127.0.0.1',
            'Test Browser'
        );

        expect($result2['success'])->toBeFalse()
            ->and($result2['message'])->toContain('sudah melakukan presensi');
    });

    test('rejects scan when attendance marked as manual', function (): void {
        // Update detail to manual attendance
        $this->detail->update([
            'kehadiran' => 'hadir',
            'metode_kehadiran' => 'manual',
        ]);

        $qrData = $this->service->generateQrData($this->siswa);

        $result = $this->service->processScan(
            $qrData,
            $this->siswa,
            '127.0.0.1',
            'Test Browser'
        );

        expect($result['success'])->toBeFalse()
            ->and($result['message'])->toContain('sudah');
    });

    test('logs successful scan', function (): void {
        $qrData = $this->service->generateQrData($this->siswa);

        $this->service->processScan(
            $qrData,
            $this->siswa,
            '127.0.0.1',
            'Test Browser'
        );

        $this->assertDatabaseHas('log_scan_presensi', [
            'sesi_presensi_id' => $this->sesi->id,
            'siswa_id' => $this->siswa->id,
            'status_scan' => 'berhasil',
        ]);
    });

    test('logs failed scan', function (): void {
        $this->service->closeSession($this->sesi);
        $qrData = $this->service->generateQrData($this->siswa);

        $this->service->processScan(
            $qrData,
            $this->siswa,
            '127.0.0.1',
            'Test Browser'
        );

        $this->assertDatabaseHas('log_scan_presensi', [
            'siswa_id' => $this->siswa->id,
            'status_scan' => 'gagal',
            'alasan_gagal' => 'sesi_tidak_aktif',
        ]);
    });
});
