<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Keaktifan;
use App\Enums\KehadiranStatus;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;

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

    /**
     * @return HasMany<DetailAktivitas, $this>
     */
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
     * Get the average keaktifan weight for this student.
     *
     * @param  int|null  $mataPelajaranId  Filter by specific subject (optional)
     * @param  string|null  $startDate  Filter from date (optional)
     * @param  string|null  $endDate  Filter to date (optional)
     * @param  int|null  $tahunAjaranId  Filter by academic year (optional)
     */
    public function getAverageKeaktifan(
        ?int $mataPelajaranId = null,
        ?string $startDate = null,
        ?string $endDate = null,
        ?int $tahunAjaranId = null
    ): ?float {
        if ($this->needsQuery($mataPelajaranId, $startDate, $endDate, $tahunAjaranId)) {
            $values = $this->buildFilteredDetailQuery($mataPelajaranId, $startDate, $endDate, $tahunAjaranId)
                ->whereNotNull('detail_aktivitas.keaktifan')
                ->pluck('detail_aktivitas.keaktifan');

            return $this->averageKeaktifanValues($values);
        }

        $values = $this->detailAktivitas->whereNotNull('keaktifan')->pluck('keaktifan');

        return $this->averageKeaktifanValues($values);
    }

    /**
     * @param  int|null  $mataPelajaranId  Filter by specific subject (optional)
     * @param  string|null  $startDate  Filter from date (optional)
     * @param  string|null  $endDate  Filter to date (optional)
     * @param  int|null  $tahunAjaranId  Filter by academic year (optional)
     */
    public function getAverageKeaktifanLabel(
        ?int $mataPelajaranId = null,
        ?string $startDate = null,
        ?string $endDate = null,
        ?int $tahunAjaranId = null
    ): string {
        $avg = $this->getAverageKeaktifan($mataPelajaranId, $startDate, $endDate, $tahunAjaranId);

        return $avg === null ? '-' : Keaktifan::fromAverage($avg)->label();
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
        if (! $this->needsQuery($mataPelajaranId, null, null, null)) {
            return $this->computeAttendanceBreakdown($this->detailAktivitas);
        }

        $query = $this->detailAktivitas()
            ->join('aktivitas_pembelajaran', 'detail_aktivitas.aktivitas_pembelajaran_id', '=', 'aktivitas_pembelajaran.id')
            ->whereNull('aktivitas_pembelajaran.deleted_at');

        if ($mataPelajaranId !== null && $mataPelajaranId !== 0) {
            $query->where('aktivitas_pembelajaran.mata_pelajaran_id', $mataPelajaranId);
        }

        $details = $query->get(['detail_aktivitas.kehadiran']);

        return $this->computeAttendanceBreakdown($details);
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

        $streak = 0;
        foreach ($query->select('detail_aktivitas.kehadiran')->lazy(100) as $record) {
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
     * Get the average keaktifan weight attribute (all activities).
     */
    protected function averageKeaktifan(): Attribute
    {
        return Attribute::make(
            get: fn (): ?float => $this->getAverageKeaktifan(),
        );
    }

    /**
     * Get the average keaktifan as a human-readable label (all activities).
     * Access via $siswa->average_keaktifan_label.
     */
    protected function averageKeaktifanLabel(): Attribute
    {
        return Attribute::make(
            get: fn (): string => $this->getAverageKeaktifanLabel(),
        );
    }

    /**
     * @param  Collection<int, DetailAktivitas>  $details
     * @return array{total: int, hadir: int, izin: int, sakit: int, alpa: int}
     */
    private function computeAttendanceBreakdown(Collection $details): array
    {
        return [
            'total' => $details->count(),
            'hadir' => $details->filter(fn ($d): bool => $d->kehadiran === KehadiranStatus::Hadir)->count(),
            'izin' => $details->filter(fn ($d): bool => $d->kehadiran === KehadiranStatus::Izin)->count(),
            'sakit' => $details->filter(fn ($d): bool => $d->kehadiran === KehadiranStatus::Sakit)->count(),
            'alpa' => $details->filter(fn ($d): bool => $d->kehadiran === KehadiranStatus::Alpa)->count(),
        ];
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

    /**
     * @param  Collection<int, Keaktifan|string>  $values
     */
    private function averageKeaktifanValues(Collection $values): ?float
    {
        if ($values->isEmpty()) {
            return null;
        }

        $weights = $values->map(
            fn (Keaktifan|string $value): int => ($value instanceof Keaktifan ? $value : Keaktifan::from($value))->weight()
        );

        return round((float) $weights->avg(), 2);
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
