<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

final class MataPelajaran extends Model
{
    use HasFactory;
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
}
