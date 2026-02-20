<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
}
