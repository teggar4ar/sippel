<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TahunAjaran extends Model
{
    use SoftDeletes;

    protected $table = 'tahun_ajaran';

    protected $fillable = [
        'nama_tahun',
        'semester',
        'tanggal_mulai',
        'tanggal_selesai',
        'status',
    ];

    protected $casts = [
        'tanggal_mulai' => 'date',
        'tanggal_selesai' => 'date',
        'status' => 'boolean',
    ];

    /**
     * Get all classes for this academic year
     */
    public function kelas(): HasMany
    {
        return $this->hasMany(Kelas::class);
    }

    /**
     * Get all reports for this academic year
     */
    public function laporan(): HasMany
    {
        return $this->hasMany(Laporan::class);
    }
}
