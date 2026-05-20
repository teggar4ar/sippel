<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;

final class MataPelajaran extends Model
{
    use SoftDeletes;

    protected $table = 'mata_pelajaran';

    protected $fillable = [
        'nama_mapel',
        'guru_id',
        'kelas_id',
    ];

    /**
     * Get the teacher for this subject
     */
    public function guru(): BelongsTo
    {
        return $this->belongsTo(User::class, 'guru_id');
    }

    /**
     * Get the class for this subject
     */
    public function kelas(): BelongsTo
    {
        return $this->belongsTo(Kelas::class);
    }

    /**
     * Get all learning activities for this subject
     */
    public function aktivitasPembelajaran(): HasMany
    {
        return $this->hasMany(AktivitasPembelajaran::class);
    }

    /**
     * Get all reports for this subject
     */
    public function laporan(): HasMany
    {
        return $this->hasMany(Laporan::class);
    }

    protected static function booted(): void
    {
        self::created(function (self $mapel): void {
            $mapel->clearDashboardCacheForContext($mapel->guru_id, $mapel->kelas_id);
        });

        self::updated(function (self $mapel): void {
            $originalGuruId = $mapel->getOriginal('guru_id');
            $originalKelasId = $mapel->getOriginal('kelas_id');

            $mapel->clearDashboardCacheForContext($originalGuruId, $originalKelasId);
            $mapel->clearDashboardCacheForContext($mapel->guru_id, $mapel->kelas_id);
        });

        self::deleted(function (self $mapel): void {
            $mapel->clearDashboardCacheForContext($mapel->guru_id, $mapel->kelas_id);
        });

        self::restored(function (self $mapel): void {
            $mapel->clearDashboardCacheForContext($mapel->guru_id, $mapel->kelas_id);
        });
    }

    private function clearDashboardCacheForContext(?int $guruId, ?int $kelasId): void
    {
        if (! $guruId || ! $kelasId) {
            return;
        }

        $kelas = Kelas::withTrashed()->find($kelasId);
        if (! $kelas) {
            return;
        }

        $tahunAjaranId = $kelas->tahun_ajaran_id;
        Cache::forget('teacher_dashboard_stats_'.$guruId.'_'.$tahunAjaranId);
    }
}
