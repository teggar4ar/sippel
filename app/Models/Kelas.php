<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

final class Kelas extends Model
{
    use SoftDeletes;

    protected $table = 'kelas';

    protected $fillable = [
        'tingkat_kelas',
        'grup_kelas',
        'wali_kelas_id',
        'tahun_ajaran_id',
    ];

    /**
     * Get the academic year that owns this class
     */
    public function tahunAjaran(): BelongsTo
    {
        return $this->belongsTo(TahunAjaran::class);
    }

    /**
     * Get the homeroom teacher (wali kelas) for this class
     */
    public function waliKelas(): BelongsTo
    {
        return $this->belongsTo(User::class, 'wali_kelas_id');
    }

    /**
     * Get all students in this class
     */
    public function siswa(): HasMany
    {
        return $this->hasMany(Siswa::class);
    }

    /**
     * Get all subjects for this class
     */
    public function mataPelajaran(): HasMany
    {
        return $this->hasMany(MataPelajaran::class);
    }

    /**
     * Get all learning activities for this class
     */
    public function aktivitasPembelajaran(): HasMany
    {
        return $this->hasMany(AktivitasPembelajaran::class);
    }

    /**
     * Get the full class name (e.g., "7A", "8B")
     */
    public function getNamaLengkapAttribute(): string
    {
        return $this->tingkat_kelas.$this->grup_kelas;
    }
}
