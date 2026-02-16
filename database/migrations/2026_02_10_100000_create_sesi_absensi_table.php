<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('sesi_presensi', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('aktivitas_pembelajaran_id')
                ->constrained('aktivitas_pembelajaran')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->enum('status', ['open', 'closed'])->default('open'); // Session status
            $table->tinyInteger('durasi_menit')->unsigned(); // Duration in minutes (5, 10, or 15)
            $table->timestamp('dibuka_pada'); // Session opened at
            $table->timestamp('ditutup_pada')->nullable(); // Session closed at
            $table->timestamps();

            // Indexes
            $table->index('aktivitas_pembelajaran_id', 'sesi_aktivitas_idx');
            $table->index('status', 'sesi_status_idx');
            $table->index(['aktivitas_pembelajaran_id', 'status'], 'sesi_aktivitas_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sesi_presensi');
    }
};
