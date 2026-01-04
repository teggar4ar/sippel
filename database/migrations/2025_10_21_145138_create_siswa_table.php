<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('siswa', function (Blueprint $table): void {
            $table->id();
            $table->string('nis', 20)->unique(); // Student ID Number
            $table->foreignId('user_id')->constrained('users')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('kelas_id')->constrained('kelas')->cascadeOnUpdate()->restrictOnDelete();
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('kelas_id', 'siswa_kelas_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('siswa');
    }
};
