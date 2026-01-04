<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('aktivitas_pembelajaran', function (Blueprint $table): void {
            $table->id();
            $table->date('tanggal'); // Activity date
            $table->string('topik', 255)->nullable(); // Topic/title
            $table->text('catatan')->nullable(); // Notes/description
            $table->foreignId('kelas_id')->constrained('kelas')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('mata_pelajaran_id')->constrained('mata_pelajaran')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('guru_id')->constrained('users')->cascadeOnUpdate()->restrictOnDelete();
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('tanggal');
            $table->index(['kelas_id', 'tanggal']); // Composite index for filtering
            $table->index(['kelas_id', 'mata_pelajaran_id', 'tanggal'], 'aktivitas_kelas_mapel_tanggal_idx');
            $table->index(['mata_pelajaran_id', 'tanggal'], 'aktivitas_mapel_tanggal_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('aktivitas_pembelajaran');
    }
};
