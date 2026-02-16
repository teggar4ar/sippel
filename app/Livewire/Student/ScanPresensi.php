<?php

declare(strict_types=1);

namespace App\Livewire\Student;

use App\Models\Siswa;
use App\Models\User;
use App\Services\QrAttendanceService;
use Exception;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.student')]
#[Title('Scan Presensi QR - SIPPEL Siswa')]
final class ScanPresensi extends Component
{
    public string $scanState = 'idle'; // idle, scanning, processing, success, error

    public string $message = '';

    public ?string $errorType = null;

    public bool $cameraReady = false;

    public function mount(): void
    {
        /** @var User|null $user */
        $user = Auth::user();

        // Ensure only students can access
        if (! $user || ! $user->hasRole('student')) {
            abort(403);
        }

        // Check if student has QR code
        $siswa = $user->siswa;
        if (! $siswa instanceof Siswa || ! $siswa->hasQrCode()) {
            $this->scanState = 'error';
            $this->errorType = 'no_qr';
            $this->message = 'QR code Anda belum tersedia. Hubungi admin sekolah.';
        }
    }

    public function processScan(string $qrData): void
    {
        // Rate limiting: max 10 scans per minute
        $key = 'qr-scan:'.Auth::id();

        if (RateLimiter::tooManyAttempts($key, 10)) {
            $this->scanState = 'error';
            $this->errorType = 'rate_limit';
            $this->message = 'Terlalu banyak percobaan. Tunggu sebentar.';

            return;
        }

        RateLimiter::hit($key, 60);

        $this->scanState = 'processing';
        $this->dispatch('stopScanning');

        /** @var User $user */
        $user = Auth::user();

        /** @var Siswa $siswa */
        $siswa = $user->siswa;

        try {
            $service = app(QrAttendanceService::class);

            $result = $service->processScan(
                $qrData,
                $siswa,
                request()->ip() ?? '',
                request()->userAgent() ?? ''
            );

            if ($result['success']) {
                $this->scanState = 'success';
                $this->message = $result['message'];

                // Auto-redirect after 3 seconds
                $this->dispatch('attendance-recorded');
            } else {
                $this->scanState = 'error';
                $this->errorType = $this->determineErrorType($result['message']);
                $this->message = $result['message'];
            }
        } catch (Exception $e) {
            $this->scanState = 'error';
            $this->errorType = 'system_error';
            $this->message = 'Terjadi kesalahan sistem. Silakan coba lagi.';

            // Log error for debugging
            logger()->error('QR Scan Error', [
                'siswa_id' => $siswa->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function resetScan(): void
    {
        $this->scanState = 'idle';
        $this->message = '';
        $this->errorType = null;
        $this->dispatch('resetScanner');
    }

    public function render(): View
    {
        return view('livewire.student.scan-presensi');
    }

    private function determineErrorType(string $message): string
    {
        return match (true) {
            str_contains($message, 'tidak aktif') => 'no_session',
            str_contains($message, 'berakhir') => 'session_expired',
            str_contains($message, 'sudah') => 'already_scanned',
            str_contains($message, 'bukan milik') => 'wrong_qr',
            str_contains($message, 'tidak terdaftar') => 'wrong_class',
            str_contains($message, 'tidak valid') => 'invalid_qr',
            default => 'general_error',
        };
    }
}
