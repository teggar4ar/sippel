<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::create('tahun_ajaran', function (Blueprint $table): void {
            $table->id();
            $table->string('nama_tahun', 20); // e.g., "2024/2025"
            $table->enum('semester', ['Ganjil', 'Genap'])->default('Ganjil');
            $table->date('tanggal_mulai')->nullable();
            $table->date('tanggal_selesai')->nullable();
            $table->boolean('status')->default(false);
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('status');
            $table->unique(['nama_tahun', 'semester']); // Unique combination
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tahun_ajaran');
    }
};
