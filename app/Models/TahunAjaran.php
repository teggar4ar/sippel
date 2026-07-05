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

    private const string CONTEXT_MEMO_KEY = 'tahun_ajaran_context_model';

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
     * Get the currently active TahunAjaran
     */
    public static function getActive(): ?self
    {
        return self::where('status', true)->first();
    }

    /**
     * Get tahun ajaran context untuk user saat ini
     * Priority:
     * 1. Session context (jika user sudah memilih)
     * 2. Fallback ke tahun ajaran aktif (status = true)
     */
    public static function getContext(): ?self
    {
        $request = request();

        if ($request->attributes->has(self::CONTEXT_MEMO_KEY)) {
            $memoized = $request->attributes->get(self::CONTEXT_MEMO_KEY);

            return $memoized instanceof self ? $memoized : null;
        }

        $tahunAjaran = null;
        $contextId = session('tahun_ajaran_context');

        if ($contextId) {
            $tahunAjaran = self::find($contextId);
        }

        if (! $tahunAjaran instanceof self) {
            $tahunAjaran = self::getActive();
        }

        $request->attributes->set(self::CONTEXT_MEMO_KEY, $tahunAjaran);

        return $tahunAjaran;
    }

    /**
     * Set tahun ajaran context untuk user saat ini
     */
    public static function setContext(?int $tahunAjaranId): void
    {
        if ($tahunAjaranId === null) {
            session()->forget('tahun_ajaran_context');
        } else {
            session(['tahun_ajaran_context' => $tahunAjaranId]);
        }

        self::forgetContextMemo();
    }

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

    /**
     * Clear the request-scoped context memo.
     */
    private static function forgetContextMemo(): void
    {
        if (app()->bound('request')) {
            request()->attributes->remove(self::CONTEXT_MEMO_KEY);
        }
    }
}
