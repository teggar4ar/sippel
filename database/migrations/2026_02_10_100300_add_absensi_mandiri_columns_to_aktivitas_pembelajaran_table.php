<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::table('aktivitas_pembelajaran', function (Blueprint $table): void {
            $table->boolean('absensi_mandiri')->default(false)->after('catatan'); // Self-attendance mode enabled
            $table->tinyInteger('durasi_absensi_menit')->unsigned()->nullable()->after('absensi_mandiri'); // QR session duration (5, 10, or 15 minutes)

            // Indexes
            $table->index('absensi_mandiri', 'aktivitas_absensi_mandiri_idx');
        });
    }

    public function down(): void
    {
        Schema::table('aktivitas_pembelajaran', function (Blueprint $table): void {
            $table->dropIndex('aktivitas_absensi_mandiri_idx');
            $table->dropColumn(['absensi_mandiri', 'durasi_absensi_menit']);
        });
    }
};
