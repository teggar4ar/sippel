<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::table('detail_aktivitas', function (Blueprint $table): void {
            $table->enum('metode_kehadiran', ['manual', 'qr_scan'])->nullable()->after('kehadiran'); // Attendance method (null = awaiting QR scan)
            $table->timestamp('waktu_kehadiran')->nullable()->after('metode_kehadiran'); // Attendance timestamp (for QR scan)

            // Indexes
            $table->index('metode_kehadiran', 'detail_metode_kehadiran_idx');
        });
    }

    public function down(): void
    {
        Schema::table('detail_aktivitas', function (Blueprint $table): void {
            $table->dropIndex('detail_metode_kehadiran_idx');
            $table->dropColumn(['metode_kehadiran', 'waktu_kehadiran']);
        });
    }
};
