<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\Keaktifan;
use App\Enums\KehadiranStatus;
use App\Models\DetailAktivitas;
use App\Models\Laporan;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

final class LaporanCalculatorService
{
    /**
     * Recalculate laporan stats for a specific student + subject + academic year.
     *
     * @return string 'created', 'updated', or 'skipped'
     */
    public function recalculateForCombination(
        int $siswaId,
        int $mataPelajaranId,
        int $tahunAjaranId,
        bool $forceDeleteEmpty = false
    ): string {
        $detailAktivitas = DetailAktivitas::query()
            ->where('siswa_id', $siswaId)
            ->whereHas('aktivitasPembelajaran', function ($query) use ($mataPelajaranId): void {
                $query->where('mata_pelajaran_id', $mataPelajaranId)
                    ->whereNull('deleted_at');
            })
            ->get();

        if ($detailAktivitas->isEmpty()) {
            if ($forceDeleteEmpty) {
                Laporan::where('siswa_id', $siswaId)
                    ->where('mata_pelajaran_id', $mataPelajaranId)
                    ->where('tahun_ajaran_id', $tahunAjaranId)
                    ->delete();
            }

            return 'skipped';
        }

        $stats = $this->calculateStatistics($detailAktivitas);

        $laporan = Laporan::withTrashed()
            ->where('siswa_id', $siswaId)
            ->where('mata_pelajaran_id', $mataPelajaranId)
            ->where('tahun_ajaran_id', $tahunAjaranId)
            ->first();

        $isNew = $laporan === null;

        if ($isNew) {
            $laporan = new Laporan();
            $laporan->siswa_id = $siswaId;
            $laporan->mata_pelajaran_id = $mataPelajaranId;
            $laporan->tahun_ajaran_id = $tahunAjaranId;
        }

        if ($laporan->trashed()) {
            $laporan->restore();
        }

        $laporan->rata_kehadiran = $stats['rata_kehadiran'];
        $laporan->hadir_count = $stats['hadir_count'];
        $laporan->izin_count = $stats['izin_count'];
        $laporan->sakit_count = $stats['sakit_count'];
        $laporan->alpa_count = $stats['alpa_count'];
        $laporan->total_kehadiran = $stats['total_kehadiran'];
        $laporan->rata_keaktifan = $stats['rata_keaktifan'];
        $laporan->save();

        return $isNew ? 'created' : 'updated';
    }

    /**
     * Recalculate laporan stats based on a DetailAktivitas instance.
     *
     * @return string 'created', 'updated', or 'skipped'
     */
    public function recalculateForDetail(DetailAktivitas $detailAktivitas, bool $forceDeleteEmpty = false): string
    {
        $detailAktivitas->loadMissing('aktivitasPembelajaran.kelas');

        $aktivitas = $detailAktivitas->aktivitasPembelajaran;
        if (! $aktivitas || ! $aktivitas->kelas) {
            return 'skipped';
        }

        $mataPelajaranId = $aktivitas->mata_pelajaran_id;
        $tahunAjaranId = $aktivitas->kelas->tahun_ajaran_id;

        if (! $mataPelajaranId || ! $tahunAjaranId) {
            return 'skipped';
        }

        return $this->recalculateForCombination(
            $detailAktivitas->siswa_id,
            $mataPelajaranId,
            $tahunAjaranId,
            $forceDeleteEmpty
        );
    }

    /**
     * @param  EloquentCollection<int, DetailAktivitas>  $detailAktivitas
     * @return array{rata_kehadiran: float, hadir_count: int, izin_count: int, sakit_count: int, alpa_count: int, total_kehadiran: int, rata_keaktifan: Keaktifan|null}
     */
    private function calculateStatistics(EloquentCollection $detailAktivitas): array
    {
        $total = $detailAktivitas->count();

        // Single-pass count per attendance status (replaces 4 separate
        // filter()->count() iterations over the same collection).
        $counts = $detailAktivitas->countBy(fn ($d): string => $d->kehadiran->value);

        $hadirCount = $counts[KehadiranStatus::Hadir->value] ?? 0;
        $izinCount = $counts[KehadiranStatus::Izin->value] ?? 0;
        $sakitCount = $counts[KehadiranStatus::Sakit->value] ?? 0;
        $alpaCount = $counts[KehadiranStatus::Alpa->value] ?? 0;

        $rataKehadiran = $total > 0 ? round(($hadirCount / $total) * 100, 2) : 0;

        $keaktifanWeights = $detailAktivitas
            ->whereNotNull('keaktifan')
            ->map(fn (DetailAktivitas $detail): int => $detail->keaktifan->weight());
        $rataKeaktifan = $keaktifanWeights->isNotEmpty()
            ? Keaktifan::fromAverage((float) $keaktifanWeights->avg())
            : null;

        return [
            'rata_kehadiran' => $rataKehadiran,
            'hadir_count' => $hadirCount,
            'izin_count' => $izinCount,
            'sakit_count' => $sakitCount,
            'alpa_count' => $alpaCount,
            'total_kehadiran' => $total,
            'rata_keaktifan' => $rataKeaktifan,
        ];
    }
}
