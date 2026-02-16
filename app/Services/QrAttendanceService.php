<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AktivitasPembelajaran;
use App\Models\DetailAktivitas;
use App\Models\Kelas;
use App\Models\LogScanAbsensi;
use App\Models\SesiAbsensi;
use App\Models\Siswa;
use Barryvdh\DomPDF\Facade\Pdf;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Support\Facades\DB;


final class QrAttendanceService
{
    /**
     * Generate QR data string for a student
     * Format: {siswa_id}:{hmac_signature}
     */
    public function generateQrData(Siswa $siswa): string
    {
        if (! $siswa->hasQrCode()) {
            $siswa->generateQrSecret();
        }

        $signature = $this->generateHmacSignature($siswa->id, $siswa->qr_secret);

        return "{$siswa->id}:{$signature}";
    }

    /**
     * Generate HMAC signature for QR code validation
     */
    public function generateHmacSignature(int $siswaId, string $secret): string
    {
        return hash_hmac('sha256', (string) $siswaId, $secret);
    }

    /**
     * Generate QR code image for a student
     *
     * @return string Base64 encoded PNG image
     */
    public function generateQrImage(Siswa $siswa, int $size = 300): string
    {
        $qrData = $this->generateQrData($siswa);

        $qrCode = new QrCode(
            data: $qrData,
            encoding: new Encoding('UTF-8'),
            errorCorrectionLevel: ErrorCorrectionLevel::High,
            size: $size,
            margin: 10,
            roundBlockSizeMode: RoundBlockSizeMode::Margin
        );

        $writer = new PngWriter();
        $result = $writer->write($qrCode);

        return $result->getDataUri();
    }

    /**
     * Generate batch PDF with QR cards for all students in a class
     *
     * @return string PDF content as binary string
     */
    public function generateClassQrPdf(Kelas $kelas): string
    {
        // Eager load students with their users
        $kelas->load(['siswa.user', 'tahunAjaran']);

        // Generate QR codes for all students
        $studentsWithQr = $kelas->siswa->map(function (Siswa $siswa) {
            // Auto-generate QR secret if not exists
            if (! $siswa->hasQrCode()) {
                $siswa->generateQrSecret();
            }

            return [
                'siswa' => $siswa,
                'qr_image' => $this->generateQrImage($siswa, 250),
            ];
        });

        // Generate PDF
        $pdf = Pdf::loadView('pdf.qr-cards-class', [
            'kelas' => $kelas,
            'students' => $studentsWithQr,
        ]);

        $pdf->setPaper('a4', 'portrait');

        // Return PDF content as string
        return $pdf->output();
    }

    /**
     * Validate QR code data and extract student ID
     *
     * @return array{valid: bool, siswa_id: ?int, error: ?string}
     */
    public function validateQrData(string $qrData): array
    {
        // Parse QR data
        $parts = explode(':', $qrData, 2);

        if (count($parts) !== 2) {
            return [
                'valid' => false,
                'siswa_id' => null,
                'error' => 'Format QR code tidak valid',
            ];
        }

        [$siswaId, $signature] = $parts;

        // Validate student ID is numeric
        if (! is_numeric($siswaId)) {
            return [
                'valid' => false,
                'siswa_id' => null,
                'error' => 'ID siswa tidak valid',
            ];
        }

        $siswaId = (int) $siswaId;

        // Find student
        $siswa = Siswa::find($siswaId);

        if (! $siswa) {
            return [
                'valid' => false,
                'siswa_id' => $siswaId,
                'error' => 'Siswa tidak ditemukan',
            ];
        }

        // Validate student has QR secret
        if (! $siswa->hasQrCode()) {
            return [
                'valid' => false,
                'siswa_id' => $siswaId,
                'error' => 'QR code belum di-generate untuk siswa ini',
            ];
        }

        // Validate HMAC signature
        $expectedSignature = $this->generateHmacSignature($siswaId, $siswa->qr_secret);

        if (! hash_equals($expectedSignature, $signature)) {
            return [
                'valid' => false,
                'siswa_id' => $siswaId,
                'error' => 'Signature QR code tidak valid',
            ];
        }

        return [
            'valid' => true,
            'siswa_id' => $siswaId,
            'error' => null,
        ];
    }

    /**
     * Create a new attendance session for an activity
     */
    public function createSession(
        AktivitasPembelajaran $aktivitas,
        int $durasiMenit
    ): SesiAbsensi {
        return SesiAbsensi::create([
            'aktivitas_pembelajaran_id' => $aktivitas->id,
            'status' => 'open',
            'durasi_menit' => $durasiMenit,
            'dibuka_pada' => now(),
        ]);
    }

    /**
     * Close an attendance session
     */
    public function closeSession(SesiAbsensi $sesi): bool
    {
        $sesi->status = 'closed';
        $sesi->ditutup_pada = now();

        return $sesi->save();
    }

    /**
     * Check if a session is active (open and not expired)
     */
    public function isSessionActive(SesiAbsensi $sesi): bool
    {
        return $sesi->isActive();
    }

    /**
     * Process QR code scan for student attendance
     *
     * @return array{success: bool, message: string, detail_aktivitas_id: ?int}
     */
    public function processScan(
        string $qrData,
        Siswa $siswa,
        string $ipAddress,
        string $userAgent
    ): array {
        // Validate QR code format and signature
        $validation = $this->validateQrData($qrData);

        if (! $validation['valid']) {
            $this->logFailedScan(
                null,
                $siswa->id,
                'signature_invalid',
                $ipAddress,
                $userAgent
            );

            return [
                'success' => false,
                'message' => $validation['error'] ?? 'QR code tidak valid',
                'detail_aktivitas_id' => null,
            ];
        }

        // BR-11: Self-scan only - verify QR belongs to logged-in student
        if ($validation['siswa_id'] !== $siswa->id) {
            $this->logFailedScan(
                null,
                $siswa->id,
                'bukan_pemilik_qr',
                $ipAddress,
                $userAgent
            );

            return [
                'success' => false,
                'message' => 'QR code ini bukan milik Anda',
                'detail_aktivitas_id' => null,
            ];
        }

        // BR-12: Find active session
        $sesi = SesiAbsensi::where('status', 'open')
            ->whereHas('aktivitasPembelajaran', function ($query) use ($siswa): void {
                $query->where('kelas_id', $siswa->kelas_id);
            })
            ->latest('dibuka_pada')
            ->first();

        if (! $sesi) {
            $this->logFailedScan(
                null,
                $siswa->id,
                'sesi_tidak_aktif',
                $ipAddress,
                $userAgent
            );

            return [
                'success' => false,
                'message' => 'Tidak ada sesi absensi yang sedang aktif untuk kelas Anda',
                'detail_aktivitas_id' => null,
            ];
        }

        // Check if session is expired
        if (! $this->isSessionActive($sesi)) {
            $this->logFailedScan(
                $sesi->id,
                $siswa->id,
                'sesi_expired',
                $ipAddress,
                $userAgent
            );

            return [
                'success' => false,
                'message' => 'Sesi absensi sudah berakhir',
                'detail_aktivitas_id' => null,
            ];
        }

        // BR-13: Validate student is in the same class
        $aktivitas = $sesi->aktivitasPembelajaran;

        if ($aktivitas->kelas_id !== $siswa->kelas_id) {
            $this->logFailedScan(
                $sesi->id,
                $siswa->id,
                'kelas_salah',
                $ipAddress,
                $userAgent
            );

            return [
                'success' => false,
                'message' => 'Anda tidak terdaftar di kelas ini',
                'detail_aktivitas_id' => null,
            ];
        }

        // BR-14: Check if student already scanned (one scan per session)
        $existingScan = LogScanAbsensi::where('sesi_absensi_id', $sesi->id)
            ->where('siswa_id', $siswa->id)
            ->where('status_scan', 'berhasil')
            ->exists();

        if ($existingScan) {
            $this->logFailedScan(
                $sesi->id,
                $siswa->id,
                'sudah_absen',
                $ipAddress,
                $userAgent
            );

            return [
                'success' => false,
                'message' => 'Anda sudah melakukan absensi untuk sesi ini',
                'detail_aktivitas_id' => null,
            ];
        }

        // Process successful scan within transaction
        return DB::transaction(function () use ($sesi, $siswa, $ipAddress, $userAgent): array {
            // Update or create detail_aktivitas
            $detailAktivitas = DetailAktivitas::where('aktivitas_pembelajaran_id', $sesi->aktivitas_pembelajaran_id)
                ->where('siswa_id', $siswa->id)
                ->first();

            if ($detailAktivitas) {
                // BR-16-17: Only update if not manually set by teacher
                if ($detailAktivitas->metode_kehadiran === 'manual') {
                    $this->logFailedScan(
                        $sesi->id,
                        $siswa->id,
                        'sudah_diisi_manual',
                        $ipAddress,
                        $userAgent
                    );

                    return [
                        'success' => false,
                        'message' => 'Kehadiran Anda sudah dicatat oleh guru',
                        'detail_aktivitas_id' => $detailAktivitas->id,
                    ];
                }

                $detailAktivitas->kehadiran = 'hadir';
                $detailAktivitas->metode_kehadiran = 'qr_scan';
                $detailAktivitas->waktu_kehadiran = now();
                $detailAktivitas->save();
            } else {
                $detailAktivitas = DetailAktivitas::create([
                    'aktivitas_pembelajaran_id' => $sesi->aktivitas_pembelajaran_id,
                    'siswa_id' => $siswa->id,
                    'kehadiran' => 'hadir',
                    'metode_kehadiran' => 'qr_scan',
                    'waktu_kehadiran' => now(),
                ]);
            }

            // Log successful scan
            $this->logSuccessfulScan(
                $sesi->id,
                $siswa->id,
                $ipAddress,
                $userAgent
            );

            return [
                'success' => true,
                'message' => 'Absensi berhasil dicatat',
                'detail_aktivitas_id' => $detailAktivitas->id,
            ];
        });
    }

    /**
     * Log a successful scan attempt
     */
    private function logSuccessfulScan(
        int $sesiId,
        int $siswaId,
        string $ipAddress,
        string $userAgent
    ): void {
        LogScanAbsensi::create([
            'sesi_absensi_id' => $sesiId,
            'siswa_id' => $siswaId,
            'status_scan' => 'berhasil',
            'alasan_gagal' => null,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'waktu_scan' => now(),
        ]);
    }

    /**
     * Log a failed scan attempt
     */
    private function logFailedScan(
        ?int $sesiId,
        int $siswaId,
        string $reason,
        string $ipAddress,
        string $userAgent
    ): void {
        LogScanAbsensi::create([
            'sesi_absensi_id' => $sesiId,
            'siswa_id' => $siswaId,
            'status_scan' => 'gagal',
            'alasan_gagal' => $reason,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'waktu_scan' => now(),
        ]);
    }

    /**
     * Auto-close expired sessions
     *
     * @return int Number of sessions closed
     */
    public function autoCloseExpiredSessions(): int
    {
        $expiredSessions = SesiAbsensi::where('status', 'open')
            ->get()
            ->filter(fn(SesiAbsensi $sesi): bool => ! $sesi->isActive());

        $count = 0;

        foreach ($expiredSessions as $sesi) {
            if ($this->closeSession($sesi)) {
                $count++;
            }
        }

        return $count;
    }
}
