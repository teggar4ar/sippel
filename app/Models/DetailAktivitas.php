<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Keaktifan;
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
        'keaktifan',
        'catatan',
        'aktivitas_pembelajaran_id',
        'siswa_id',
    ];

    protected $casts = [
        'kehadiran' => KehadiranStatus::class,
        'keaktifan' => Keaktifan::class,
    ];

    public function aktivitasPembelajaran(): BelongsTo
    {
        return $this->belongsTo(AktivitasPembelajaran::class);
    }

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
        $query->join('aktivitas_pembelajaran', 'detail_aktivitas.aktivitas_pembelajaran_id', '=', 'aktivitas_pembelajaran.id')
            ->join('kelas', 'aktivitas_pembelajaran.kelas_id', '=', 'kelas.id')
            ->where('kelas.tahun_ajaran_id', $tahunAjaranId)
            ->when($kelasId !== null, fn ($builder) => $builder->where('aktivitas_pembelajaran.kelas_id', $kelasId))
            ->whereNull('aktivitas_pembelajaran.deleted_at')
            ->whereNull('kelas.deleted_at')
            ->with('aktivitasPembelajaran.mataPelajaran')
            ->orderByDesc('aktivitas_pembelajaran.tanggal')
            ->orderByDesc('detail_aktivitas.id')
            ->select('detail_aktivitas.*');
    }

    /**
     * Translate the keaktifan enum into a human-readable observation label.
     * Returns '-' when the student was not present (Hadir) or has no score recorded.
     */
    protected function labelKeaktifan(): Attribute
    {
        return Attribute::make(
            get: function (): string {
                if ($this->kehadiran !== KehadiranStatus::Hadir) {
                    return '-';
                }

                return $this->keaktifan?->label() ?? '-';
            },
        );
    }
}
