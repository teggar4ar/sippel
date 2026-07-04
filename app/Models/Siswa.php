<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\KehadiranStatus;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

final class Siswa extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'siswa';

    protected $fillable = [
        'nis',
        'user_id',
        'kelas_id',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function kelas(): BelongsTo
    {
        return $this->belongsTo(Kelas::class);
    }

    public function detailAktivitas(): HasMany
    {
        return $this->hasMany(DetailAktivitas::class);
    }

    public function laporan(): HasMany
    {
        return $this->hasMany(Laporan::class);
    }

    public function kelasHistory(): HasMany
    {
        return $this->hasMany(SiswaKelasHistory::class);
    }

    /**
     * Look up which Kelas this student was enrolled in during a given academic year.
     * Uses the already-loaded kelasHistory collection when available to avoid N+1 queries.
     * Callers in batch operations must eager-load kelasHistory.kelas to prevent per-student queries.
     */
    public function getKelasForTahunAjaran(int $tahunAjaranId): ?Kelas
    {
        if ($this->relationLoaded('kelasHistory')) {
            return $this->kelasHistory
                ->where('tahun_ajaran_id', $tahunAjaranId)
                ->first()
                ?->kelas;
        }

        return $this->kelasHistory()
            ->where('tahun_ajaran_id', $tahunAjaranId)
            ->with('kelas')
            ->first()
            ?->kelas;
    }

    /**
     * Get the attendance percentage for this student.
     * Calculates: (Total 'Hadir' / Total activities) × 100
     *
     * @param  int|null  $mataPelajaranId  Filter by specific subject (optional)
     * @param  string|null  $startDate  Filter from date (optional)
     * @param  string|null  $endDate  Filter to date (optional)
     * @param  int|null  $tahunAjaranId  Filter by academic year (optional)
     */
    public function getAttendancePercentage(
        ?int $mataPelajaranId = null,
        ?string $startDate = null,
        ?string $endDate = null,
        ?int $tahunAjaranId = null
    ): float {
        if ($this->needsQuery($mataPelajaranId, $startDate, $endDate, $tahunAjaranId)) {
            $query = $this->buildFilteredDetailQuery($mataPelajaranId, $startDate, $endDate, $tahunAjaranId);
            $total = (clone $query)->count();
            $hadir = (clone $query)->whereRaw('LOWER(detail_aktivitas.kehadiran) = ?', ['hadir'])->count();

            return $this->computeAttendancePercent($hadir, $total);
        }

        // Use pre-loaded relation for better performance
        $details = $this->detailAktivitas;
        $hadir = $details->filter(fn ($d): bool => $d->kehadiran === KehadiranStatus::Hadir)->count();

        return $this->computeAttendancePercent($hadir, $details->count());
    }

    /**
     * Get the average grade for this student.
     * Calculates: SUM(nilai) / COUNT(nilai where nilai is not null)
     *
     * @param  int|null  $mataPelajaranId  Filter by specific subject (optional)
     * @param  string|null  $startDate  Filter from date (optional)
     * @param  string|null  $endDate  Filter to date (optional)
     * @param  int|null  $tahunAjaranId  Filter by academic year (optional)
     */
    public function getAverageGrade(
        ?int $mataPelajaranId = null,
        ?string $startDate = null,
        ?string $endDate = null,
        ?int $tahunAjaranId = null
    ): ?float {
        if ($this->needsQuery($mataPelajaranId, $startDate, $endDate, $tahunAjaranId)) {
            $query = $this->buildFilteredDetailQuery($mataPelajaranId, $startDate, $endDate, $tahunAjaranId)
                ->whereNotNull('detail_aktivitas.nilai');
            $avg = $query->avg('detail_aktivitas.nilai');

            return $avg !== null ? round((float) $avg, 2) : null;
        }

        // Use pre-loaded relation for better performance
        $grades = $this->detailAktivitas->whereNotNull('nilai')->pluck('nilai');

        return $grades->isNotEmpty() ? round($grades->avg(), 2) : null;
    }

    /**
     * Get the average participation for this student.
     * Calculates: SUM(partisipasi) / COUNT(partisipasi where partisipasi is not null)
     *
     * @param  int|null  $mataPelajaranId  Filter by specific subject (optional)
     * @param  string|null  $startDate  Filter from date (optional)
     * @param  string|null  $endDate  Filter to date (optional)
     * @param  int|null  $tahunAjaranId  Filter by academic year (optional)
     */
    public function getAverageParticipation(
        ?int $mataPelajaranId = null,
        ?string $startDate = null,
        ?string $endDate = null,
        ?int $tahunAjaranId = null
    ): ?float {
        if ($this->needsQuery($mataPelajaranId, $startDate, $endDate, $tahunAjaranId)) {
            $query = $this->buildFilteredDetailQuery($mataPelajaranId, $startDate, $endDate, $tahunAjaranId)
                ->whereNotNull('detail_aktivitas.partisipasi');
            $avg = $query->avg('detail_aktivitas.partisipasi');

            return $avg !== null ? round((float) $avg, 2) : null;
        }

        // Use pre-loaded relation for better performance
        $participation = $this->detailAktivitas->whereNotNull('partisipasi')->pluck('partisipasi');

        return $participation->isNotEmpty() ? round($participation->avg(), 2) : null;
    }

    /**
     * Convert a numeric average participation score to an observation label.
     * Thresholds: <1.5 = Pasif, <2.5 = Cukup, <3.5 = Aktif, >=3.5 = Sangat Aktif.
     *
     * @param  int|null  $mataPelajaranId  Filter by specific subject (optional)
     * @param  string|null  $startDate  Filter from date (optional)
     * @param  string|null  $endDate  Filter to date (optional)
     * @param  int|null  $tahunAjaranId  Filter by academic year (optional)
     */
    public function getAverageParticipationLabel(
        ?int $mataPelajaranId = null,
        ?string $startDate = null,
        ?string $endDate = null,
        ?int $tahunAjaranId = null
    ): string {
        $avg = $this->getAverageParticipation($mataPelajaranId, $startDate, $endDate, $tahunAjaranId);

        if ($avg === null) {
            return '-';
        }

        return match (true) {
            $avg < 1.5 => 'Pasif',
            $avg < 2.5 => 'Cukup',
            $avg < 3.5 => 'Aktif',
            default => 'Sangat Aktif',
        };
    }

    // =========================================================================
    // SCOPES - Query Helpers
    // =========================================================================

    /**
     * Scope to eager load relationships needed for statistics.
     * Use this to prevent N+1 queries when calculating stats for multiple students.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  int|null  $mataPelajaranId  Filter detail_aktivitas by subject
     */
    public function scopeWithStatistics($query, ?int $mataPelajaranId = null)
    {
        return $query->with([
            'user',
            'kelas',
            'detailAktivitas' => function ($q) use ($mataPelajaranId): void {
                $q->join('aktivitas_pembelajaran', 'detail_aktivitas.aktivitas_pembelajaran_id', '=', 'aktivitas_pembelajaran.id')
                    ->whereNull('aktivitas_pembelajaran.deleted_at')
                    ->select('detail_aktivitas.*');

                if ($mataPelajaranId !== null && $mataPelajaranId !== 0) {
                    $q->where('aktivitas_pembelajaran.mata_pelajaran_id', $mataPelajaranId);
                }
            },
        ]);
    }

    /**
     * Scope to filter students by class.
     */
    public function scopeInClass($query, int $kelasId)
    {
        return $query->where('kelas_id', $kelasId);
    }

    // =========================================================================
    // HELPER METHODS
    // =========================================================================

    /**
     * Get attendance breakdown for this student.
     *
     * @param  int|null  $mataPelajaranId  Filter by specific subject (optional)
     * @return array{total: int, hadir: int, izin: int, sakit: int, alpa: int}
     */
    public function getAttendanceBreakdown(?int $mataPelajaranId = null): array
    {
        $query = $this->detailAktivitas()
            ->join('aktivitas_pembelajaran', 'detail_aktivitas.aktivitas_pembelajaran_id', '=', 'aktivitas_pembelajaran.id')
            ->whereNull('aktivitas_pembelajaran.deleted_at');

        if ($mataPelajaranId !== null && $mataPelajaranId !== 0) {
            $query->where('aktivitas_pembelajaran.mata_pelajaran_id', $mataPelajaranId);
        }

        $details = $query->get(['detail_aktivitas.kehadiran']);

        return [
            'total' => $details->count(),
            'hadir' => $details->filter(fn ($d): bool => $d->kehadiran === KehadiranStatus::Hadir)->count(),
            'izin' => $details->filter(fn ($d): bool => $d->kehadiran === KehadiranStatus::Izin)->count(),
            'sakit' => $details->filter(fn ($d): bool => $d->kehadiran === KehadiranStatus::Sakit)->count(),
            'alpa' => $details->filter(fn ($d): bool => $d->kehadiran === KehadiranStatus::Alpa)->count(),
        ];
    }

    /**
     * Count consecutive "Hadir" attendance records starting from the most recent activity.
     * The streak breaks as soon as a non-"Hadir" record is found.
     *
     * @param  int|null  $tahunAjaranId  Filter by academic year (optional)
     */
    public function getAttendanceStreak(?int $tahunAjaranId = null): int
    {
        $query = $this->detailAktivitas()
            ->join('aktivitas_pembelajaran', 'detail_aktivitas.aktivitas_pembelajaran_id', '=', 'aktivitas_pembelajaran.id')
            ->whereNull('aktivitas_pembelajaran.deleted_at')
            ->orderByDesc('aktivitas_pembelajaran.tanggal')
            ->orderByDesc('detail_aktivitas.id');

        if ($tahunAjaranId !== null && $tahunAjaranId !== 0) {
            $query->join('kelas', 'aktivitas_pembelajaran.kelas_id', '=', 'kelas.id')
                ->where('kelas.tahun_ajaran_id', $tahunAjaranId);
        }

        $records = $query->select('detail_aktivitas.kehadiran')->get();

        $streak = 0;
        foreach ($records as $record) {
            if ($record->kehadiran === KehadiranStatus::Hadir) {
                $streak++;
            } else {
                break;
            }
        }

        return $streak;
    }

    /**
     * Get the attendance percentage attribute (all activities).
     */
    protected function attendancePercentage(): Attribute
    {
        return Attribute::make(
            get: fn (): float => $this->getAttendancePercentage(),
        );
    }

    /**
     * Get the average grade attribute (all activities).
     */
    protected function averageGrade(): Attribute
    {
        return Attribute::make(
            get: fn (): ?float => $this->getAverageGrade(),
        );
    }

    /**
     * Get the average participation attribute (all activities).
     */
    protected function averageParticipation(): Attribute
    {
        return Attribute::make(
            get: fn (): ?float => $this->getAverageParticipation(),
        );
    }

    /**
     * Get the average participation as a human-readable label (all activities).
     * Access via $siswa->average_participation_label.
     */
    protected function averageParticipationLabel(): Attribute
    {
        return Attribute::make(
            get: fn (): string => $this->getAverageParticipationLabel(),
        );
    }

    /**
     * Compute the attendance percentage from raw hadir/total counts.
     * Returns 0.0 when total is zero to avoid division by zero.
     */
    private function computeAttendancePercent(int $hadir, int $total): float
    {
        if ($total === 0) {
            return 0.0;
        }

        return round(($hadir / $total) * 100, 2);
    }

    // =========================================================================
    // ACCESSORS - Automatic Calculations
    // =========================================================================

    /**
     * Build a base query for detail_aktivitas joined to aktivitas_pembelajaran,
     * with optional filters applied. Shared by the three stat-calculation methods.
     */
    private function buildFilteredDetailQuery(
        ?int $mataPelajaranId,
        ?string $startDate,
        ?string $endDate,
        ?int $tahunAjaranId
    ): HasMany {
        $query = $this->detailAktivitas()
            ->join('aktivitas_pembelajaran', 'detail_aktivitas.aktivitas_pembelajaran_id', '=', 'aktivitas_pembelajaran.id')
            ->whereNull('aktivitas_pembelajaran.deleted_at');

        if ($mataPelajaranId !== null && $mataPelajaranId !== 0) {
            $query->where('aktivitas_pembelajaran.mata_pelajaran_id', $mataPelajaranId);
        }

        if (! in_array($startDate, [null, '', '0'], true)) {
            $query->where('aktivitas_pembelajaran.tanggal', '>=', $startDate);
        }

        if (! in_array($endDate, [null, '', '0'], true)) {
            $query->where('aktivitas_pembelajaran.tanggal', '<=', $endDate);
        }

        if ($tahunAjaranId !== null && $tahunAjaranId !== 0) {
            $query->join('kelas', 'aktivitas_pembelajaran.kelas_id', '=', 'kelas.id')
                ->where('kelas.tahun_ajaran_id', $tahunAjaranId);
        }

        return $query;
    }

    /**
     * Returns true when any filter is active or the relation is not yet loaded,
     * meaning we must hit the database instead of the in-memory collection.
     */
    private function needsQuery(
        ?int $mataPelajaranId,
        ?string $startDate,
        ?string $endDate,
        ?int $tahunAjaranId
    ): bool {
        return $mataPelajaranId || $startDate || $endDate || $tahunAjaranId
            || ! $this->relationLoaded('detailAktivitas');
    }
}
