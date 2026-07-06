<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

final class AktivitasPembelajaran extends Model
{
    use HasFactory, SoftDeletes;

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

    public function kelas(): BelongsTo
    {
        return $this->belongsTo(Kelas::class);
    }

    public function mataPelajaran(): BelongsTo
    {
        return $this->belongsTo(MataPelajaran::class);
    }

    public function guru(): BelongsTo
    {
        return $this->belongsTo(User::class, 'guru_id');
    }

    public function detailAktivitas(): HasMany
    {
        return $this->hasMany(DetailAktivitas::class);
    }
}
