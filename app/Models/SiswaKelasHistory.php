<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;

/**
 * Tracks a student's class enrollment for each academic year.
 *
 * This table is written to whenever a student is assigned or migrated
 * to a class (via Ganti Semester, Kenaikan Kelas, or new enrollment).
 * It allows looking up which class a student belonged to in any past
 * academic year, even after `siswa.kelas_id` has been updated.
 */
final class SiswaKelasHistory extends Model
{
    protected $table = 'siswa_kelas_history';

    protected $fillable = [
        'siswa_id',
        'kelas_id',
        'tahun_ajaran_id',
    ];

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class);
    }

    public function kelas(): BelongsTo
    {
        return $this->belongsTo(Kelas::class);
    }

    public function tahunAjaran(): BelongsTo
    {
        return $this->belongsTo(TahunAjaran::class);
    }

    protected static function booted(): void
    {
        self::created(function (self $history): void {
            $history->clearTeacherDashboardCache($history->kelas_id, $history->tahun_ajaran_id);
        });

        self::updated(function (self $history): void {
            $originalKelasId = $history->getOriginal('kelas_id');
            $originalTahunAjaranId = $history->getOriginal('tahun_ajaran_id');

            $history->clearTeacherDashboardCache($originalKelasId, $originalTahunAjaranId);
            $history->clearTeacherDashboardCache($history->kelas_id, $history->tahun_ajaran_id);
        });

        self::deleted(function (self $history): void {
            $history->clearTeacherDashboardCache($history->kelas_id, $history->tahun_ajaran_id);
        });
    }

    private function clearTeacherDashboardCache(?int $kelasId, ?int $tahunAjaranId): void
    {
        if (! $kelasId || ! $tahunAjaranId) {
            return;
        }

        $guruIds = MataPelajaran::query()
            ->where('kelas_id', $kelasId)
            ->whereHas('kelas', fn ($q) => $q->where('tahun_ajaran_id', $tahunAjaranId))
            ->pluck('guru_id')
            ->filter()
            ->unique();

        foreach ($guruIds as $guruId) {
            Cache::forget('teacher_dashboard_stats_'.$guruId.'_'.$tahunAjaranId);
        }
    }
}
