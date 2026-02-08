<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('laporan', function (Blueprint $table): void {
            $table->id();

            // Aggregated/cached statistics
            $table->float('rata_kehadiran')->default(0);
            $table->integer('hadir_count')->default(0);
            $table->integer('izin_count')->default(0);
            $table->integer('sakit_count')->default(0);
            $table->integer('alpa_count')->default(0);
            $table->integer('total_kehadiran')->default(0);
            $table->float('rata_nilai')->nullable();
            $table->integer('rata_partisipasi')->nullable();

            // Foreign keys
            $table->foreignId('siswa_id')->constrained('siswa')->cascadeOnDelete();
            $table->foreignId('mata_pelajaran_id')->constrained('mata_pelajaran')->cascadeOnDelete();
            $table->foreignId('tahun_ajaran_id')->constrained('tahun_ajaran')->cascadeOnDelete();

            $table->timestamps();
            $table->softDeletes();

            // Unique constraint: one report per student per subject per academic year
            $table->unique(['siswa_id', 'mata_pelajaran_id', 'tahun_ajaran_id'], 'laporan_unique');

            // Additional index for filtering by academic year
            $table->index('tahun_ajaran_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('laporan');
    }
};
