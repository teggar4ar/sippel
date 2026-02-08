<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

final class Laporan extends Model
{
    use SoftDeletes;

    protected $table = 'laporan';

    protected $fillable = [
        'rata_kehadiran',
        'hadir_count',
        'izin_count',
        'sakit_count',
        'alpa_count',
        'total_kehadiran',
        'rata_nilai',
        'rata_partisipasi',
        'siswa_id',
        'mata_pelajaran_id',
        'tahun_ajaran_id',
    ];

    protected $casts = [
        'rata_kehadiran' => 'float',
        'hadir_count' => 'integer',
        'izin_count' => 'integer',
        'sakit_count' => 'integer',
        'alpa_count' => 'integer',
        'total_kehadiran' => 'integer',
        'rata_nilai' => 'float',
        'rata_partisipasi' => 'integer',
    ];

    /**
     * Get the student this report belongs to
     */
    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class);
    }

    /**
     * Get the subject this report belongs to
     */
    public function mataPelajaran(): BelongsTo
    {
        return $this->belongsTo(MataPelajaran::class);
    }

    /**
     * Get the academic year this report belongs to
     */
    public function tahunAjaran(): BelongsTo
    {
        return $this->belongsTo(TahunAjaran::class);
    }
}
