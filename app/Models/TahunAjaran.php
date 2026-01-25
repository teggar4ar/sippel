<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\TahunAjaranFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

final class TahunAjaran extends Model
{
    /** @use HasFactory<TahunAjaranFactory> */
    use HasFactory, SoftDeletes;

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

    /**
     * Get the currently active TahunAjaran
     */
    public static function getActive(): ?self
    {
        return self::where('status', true)->first();
    }

    /**
     * Check if this is an odd semester (Ganjil)
     */
    public function isGanjil(): bool
    {
        return $this->semester === 'Ganjil';
    }

    /**
     * Check if this is an even semester (Genap)
     */
    public function isGenap(): bool
    {
        return $this->semester === 'Genap';
    }

    /**
     * Get the next academic year name (e.g., "2024/2025" -> "2025/2026")
     */
    public function getNextNamaTahun(): string
    {
        // Parse current year name (format: "2024/2025")
        $parts = explode('/', $this->nama_tahun);
        if (count($parts) === 2) {
            $startYear = (int) $parts[0] + 1;
            $endYear = (int) $parts[1] + 1;

            return $startYear.'/'.$endYear;
        }

        // Fallback: just return current name
        return $this->nama_tahun;
    }
}
