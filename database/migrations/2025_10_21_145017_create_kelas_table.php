<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('kelas', function (Blueprint $table): void {
            $table->id();
            $table->tinyInteger('tingkat_kelas'); // 7, 8, or 9 for junior high
            $table->char('grup_kelas', 1); // A, B, C, etc.
            $table->foreignId('wali_kelas_id')->constrained('users')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('tahun_ajaran_id')->constrained('tahun_ajaran')->cascadeOnUpdate()->restrictOnDelete();
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['tingkat_kelas', 'grup_kelas']); // Composite index
            $table->unique(['tingkat_kelas', 'grup_kelas', 'tahun_ajaran_id'], 'unique_kelas_per_tahun'); // Prevent duplicate class
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kelas');
    }
};
