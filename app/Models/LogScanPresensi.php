<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class LogScanPresensi extends Model
{
    protected $table = 'log_scan_presensi';

    protected $fillable = [
        'sesi_presensi_id',
        'siswa_id',
        'status_scan',
        'alasan_gagal',
        'ip_address',
        'user_agent',
        'waktu_scan',
    ];

    protected $casts = [
        'waktu_scan' => 'datetime',
    ];

    /**
     * Get the session for this scan log
     */
    public function sesiPresensi(): BelongsTo
    {
        return $this->belongsTo(SesiPresensi::class);
    }

    /**
     * Get the student who attempted the scan
     */
    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class);
    }

    /**
     * Check if the scan was successful
     */
    public function berhasil(): bool
    {
        return $this->status_scan === 'berhasil';
    }

    /**
     * Check if the scan failed
     */
    public function gagal(): bool
    {
        return $this->status_scan === 'gagal';
    }
}
