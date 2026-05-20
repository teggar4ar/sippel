<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\KehadiranStatus;
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
