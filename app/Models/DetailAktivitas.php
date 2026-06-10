<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\KehadiranStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

final class DetailAktivitas extends Model
{
    use SoftDeletes;

    protected $table = 'detail_aktivitas';

    protected $fillable = [
        'kehadiran',
        'nilai',
        'partisipasi',
        'catatan',
        'aktivitas_pembelajaran_id',
        'siswa_id',
    ];

    protected $casts = [
        'kehadiran' => KehadiranStatus::class,
        'nilai' => 'decimal:2',
        'partisipasi' => 'decimal:2',
    ];

    /**
     * Get the learning activity this detail belongs to
     */
    public function aktivitasPembelajaran(): BelongsTo
    {
        return $this->belongsTo(AktivitasPembelajaran::class);
    }

    /**
     * Get the student for this activity detail
     */
    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class);
    }

    /**
     * Scope query for student activity timeline: joins aktivitas_pembelajaran,
     * filters by kelas/tahun ajaran, eager-loads relationships, and orders by date descending.
     */
    public function scopeWithTimelineJoin(Builder $query, ?int $kelasId, int $tahunAjaranId): void
    {
        $query->whereHas('aktivitasPembelajaran', function ($q) use ($kelasId, $tahunAjaranId): void {
            if ($kelasId !== null) {
                $q->where('kelas_id', $kelasId);
            }
            $q->whereHas('kelas', fn ($kq) => $kq->where('tahun_ajaran_id', $tahunAjaranId));
        })
            ->with(['aktivitasPembelajaran.mataPelajaran', 'aktivitasPembelajaran'])
            ->join('aktivitas_pembelajaran', 'detail_aktivitas.aktivitas_pembelajaran_id', '=', 'aktivitas_pembelajaran.id')
            ->orderByDesc('aktivitas_pembelajaran.tanggal')
            ->orderByDesc('detail_aktivitas.id')
            ->select('detail_aktivitas.*');
    }

    /**
     * Translate the numeric participation score into a human-readable observation label.
     * Returns '-' when the student was not present (Hadir) or has no score recorded.
     */
    protected function labelPartisipasi(): Attribute
    {
        return Attribute::make(
            get: function (): string {
                if ($this->kehadiran !== KehadiranStatus::Hadir) {
                    return '-';
                }

                return match ((int) $this->partisipasi) {
                    1 => 'Pasif',
                    2 => 'Cukup',
                    3 => 'Aktif',
                    4 => 'Sangat Aktif',
                    default => '-',
                };
            },
        );
    }
}
