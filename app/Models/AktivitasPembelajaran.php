<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

final class AktivitasPembelajaran extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'aktivitas_pembelajaran';

    protected $fillable = [
        'tanggal',
        'topik',
        'catatan',
        'kelas_id',
        'mata_pelajaran_id',
        'guru_id',
        'presensi_mandiri',
        'durasi_presensi_menit',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'presensi_mandiri' => 'boolean',
        'durasi_presensi_menit' => 'integer',
    ];

    /**
     * Get the class for this learning activity
     */
    public function kelas(): BelongsTo
    {
        return $this->belongsTo(Kelas::class);
    }

    /**
     * Get the subject for this learning activity
     */
    public function mataPelajaran(): BelongsTo
    {
        return $this->belongsTo(MataPelajaran::class);
    }

    /**
     * Get the teacher who created this activity
     */
    public function guru(): BelongsTo
    {
        return $this->belongsTo(User::class, 'guru_id');
    }

    /**
     * Get all activity details (student records) for this activity
     */
    public function detailAktivitas(): HasMany
    {
        return $this->hasMany(DetailAktivitas::class);
    }

    /**
     * Get all QR attendance sessions for this activity
     */
    public function sesiPresensi(): HasMany
    {
        return $this->hasMany(SesiPresensi::class);
    }

    /**
     * Get the active (open and not expired) QR session for this activity
     */
    public function activeSesi(): ?SesiPresensi
    {
        return $this->sesiPresensi()
            ->where('status', 'open')
            ->latest('dibuka_pada')
            ->first();
    }

    /**
     * Check if this activity has an active QR attendance session
     */
    public function hasActiveSesi(): bool
    {
        $sesi = $this->activeSesi();

        return $sesi && $sesi->isActive();
    }
}
