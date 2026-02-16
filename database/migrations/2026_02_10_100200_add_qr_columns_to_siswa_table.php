<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        Schema::table('siswa', function (Blueprint $table): void {
            $table->string('qr_secret', 64)->nullable()->after('kelas_id'); // Secret key for HMAC signature
            $table->timestamp('qr_generated_at')->nullable()->after('qr_secret'); // QR generation timestamp

            // Indexes
            $table->index('qr_secret', 'siswa_qr_secret_idx');
        });
    }

    public function down(): void
    {
        Schema::table('siswa', function (Blueprint $table): void {
            $table->dropIndex('siswa_qr_secret_idx');
            $table->dropColumn(['qr_secret', 'qr_generated_at']);
        });
    }
};
