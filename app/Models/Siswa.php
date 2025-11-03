<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Siswa extends Model
{
    use SoftDeletes;

    protected $table = 'siswa';

    protected $fillable = [
        'nis',
        'user_id',
        'kelas_id',
    ];

    /**
     * Get the user account for this student
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the class this student belongs to
     */
    public function kelas(): BelongsTo
    {
        return $this->belongsTo(Kelas::class);
    }

    /**
     * Get all activity details for this student
     */
    public function detailAktivitas(): HasMany
    {
        return $this->hasMany(DetailAktivitas::class);
    }

    /**
     * Get all reports for this student
     */
    public function laporan(): HasMany
    {
        return $this->hasMany(Laporan::class);
    }
}
