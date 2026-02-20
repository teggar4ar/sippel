<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('siswa_kelas_history', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('siswa_id')->constrained('siswa')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('kelas_id')->constrained('kelas')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('tahun_ajaran_id')->constrained('tahun_ajaran')->cascadeOnUpdate()->restrictOnDelete();
            $table->timestamps();

            // One class per student per academic year
            $table->unique(['siswa_id', 'tahun_ajaran_id'], 'unique_siswa_per_tahun_ajaran');

            // Speed up lookups by year
            $table->index('tahun_ajaran_id', 'skh_tahun_ajaran_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('siswa_kelas_history');
    }
};
