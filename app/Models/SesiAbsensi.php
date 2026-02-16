<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class SesiAbsensi extends Model
{
    protected $table = 'sesi_absensi';

    protected $fillable = [
        'aktivitas_pembelajaran_id',
        'status',
        'durasi_menit',
        'dibuka_pada',
        'ditutup_pada',
    ];

    protected $casts = [
        'dibuka_pada' => 'datetime',
        'ditutup_pada' => 'datetime',
        'durasi_menit' => 'integer',
    ];

    /**
     * Get the learning activity for this session
     */
    public function aktivitasPembelajaran(): BelongsTo
    {
        return $this->belongsTo(AktivitasPembelajaran::class);
    }

    /**
     * Get all scan logs for this session
     */
    public function logScanAbsensi(): HasMany
    {
        return $this->hasMany(LogScanAbsensi::class);
    }

    /**
     * Check if the session is still active (not expired)
     */
    public function isActive(): bool
    {
        if ($this->status !== 'open') {
            return false;
        }

        $expiresAt = $this->dibuka_pada->addMinutes($this->durasi_menit);

        return now()->lessThan($expiresAt);
    }

    /**
     * Get the session expiration timestamp
     */
    public function getExpiresAtAttribute(): ?\Carbon\CarbonImmutable
    {
        if (! $this->dibuka_pada) {
            return null;
        }

        return $this->dibuka_pada->addMinutes($this->durasi_menit);
    }

    /**
     * Get remaining seconds until session expires
     */
    public function getRemainingSecondsAttribute(): int
    {
        if (! $this->isActive()) {
            return 0;
        }

        return max(0, (int) now()->diffInSeconds($this->expires_at, false));
    }
}
