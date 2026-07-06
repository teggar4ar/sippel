<?php

declare(strict_types=1);

use App\Enums\Keaktifan;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('detail_aktivitas', function (Blueprint $table): void {
            $table->id();
            $table->enum('kehadiran', ['hadir', 'izin', 'sakit', 'alpa'])->default('alpa'); // Attendance status
            $table->enum('keaktifan', Keaktifan::values())->nullable();
            $table->text('catatan')->nullable(); // Notes
            $table->foreignId('aktivitas_pembelajaran_id')->constrained('aktivitas_pembelajaran')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('siswa_id')->constrained('siswa')->cascadeOnUpdate()->cascadeOnDelete();
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('kehadiran');
            $table->unique(
                ['aktivitas_pembelajaran_id', 'siswa_id'],
                'detail_aktivitas_siswa_unique'
            );
            $table->index(['siswa_id', 'kehadiran'], 'detail_siswa_kehadiran_idx');
            $table->index(['aktivitas_pembelajaran_id', 'kehadiran'], 'detail_aktivitas_kehadiran_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('detail_aktivitas');
    }
};
