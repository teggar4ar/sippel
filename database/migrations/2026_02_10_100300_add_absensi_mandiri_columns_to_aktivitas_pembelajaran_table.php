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
            $table->boolean('presensi_mandiri')->default(false)->after('catatan'); // Self-attendance mode enabled
            $table->tinyInteger('durasi_presensi_menit')->unsigned()->nullable()->after('presensi_mandiri'); // QR session duration (5, 10, or 15 minutes)

            // Indexes
            $table->index('presensi_mandiri', 'aktivitas_presensi_mandiri_idx');
        });
    }

    public function down(): void
    {
        Schema::table('aktivitas_pembelajaran', function (Blueprint $table): void {
            $table->dropIndex('aktivitas_presensi_mandiri_idx');
            $table->dropColumn(['presensi_mandiri', 'durasi_presensi_menit']);
        });
    }
};
