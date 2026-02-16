<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('log_scan_presensi', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('sesi_presensi_id')
                ->nullable() // Nullable for early validation failures (signature, format)
                ->constrained('sesi_presensi')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->foreignId('siswa_id')
                ->constrained('siswa')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->enum('status_scan', ['berhasil', 'gagal'])->default('gagal'); // Scan status
            $table->string('alasan_gagal', 100)->nullable(); // Failure reason: sesi_expired, sudah_absen, kelas_salah, signature_invalid, bukan_pemilik_qr
            $table->string('ip_address', 45)->nullable(); // IP address of scanner
            $table->string('user_agent', 255)->nullable(); // Browser user agent
            $table->timestamp('waktu_scan'); // Scan timestamp
            $table->timestamps();

            // Indexes
            $table->index('sesi_presensi_id', 'log_sesi_idx');
            $table->index('siswa_id', 'log_siswa_idx');
            $table->index(['sesi_presensi_id', 'siswa_id'], 'log_sesi_siswa_idx');
            $table->index('status_scan', 'log_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('log_scan_presensi');
    }
};
