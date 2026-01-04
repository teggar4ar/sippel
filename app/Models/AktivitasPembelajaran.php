<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

final class AktivitasPembelajaran extends Model
{
    use SoftDeletes;

    protected $table = 'aktivitas_pembelajaran';

    protected $fillable = [
        'tanggal',
        'topik',
        'catatan',
        'kelas_id',
        'mata_pelajaran_id',
        'guru_id',
    ];

    protected $casts = [
        'tanggal' => 'date',
    ];

    /**
     * Get the class for this learning activity
     */
    public function kelas(): BelongsTo
    {
        return $this->belongsTo(Kelas::class);
    }

    /**
     * Get the subject for this learning activity
     */
    public function mataPelajaran(): BelongsTo
    {
        return $this->belongsTo(MataPelajaran::class);
    }

    /**
     * Get the teacher who created this activity
     */
    public function guru(): BelongsTo
    {
        return $this->belongsTo(User::class, 'guru_id');
    }

    /**
     * Get all activity details (student records) for this activity
     */
    public function detailAktivitas(): HasMany
    {
        return $this->hasMany(DetailAktivitas::class);
    }
}
